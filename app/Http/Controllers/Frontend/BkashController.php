<?php

namespace App\Http\Controllers\Frontend;

use App\Domain\Orders\OrderStateMachine;
use App\Domain\Payments\PaymentEventRecorder;
use App\Domain\Payments\PaymentIdempotencyService;
use App\Domain\Payments\PaymentVerificationService;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentGateway;
use App\Support\Money;
use Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Session;
use Throwable;
use Toastr;

class BkashController extends Controller
{
    private string $base_url;

    private string $app_key;

    private string $app_secret;

    private string $username;

    private string $password;

    private bool $gatewayConfigLoaded = false;

    public function __construct(
        private readonly PaymentEventRecorder $eventRecorder,
        private readonly PaymentIdempotencyService $idempotencyService,
        private readonly PaymentVerificationService $verificationService,
        private readonly OrderStateMachine $orderStateMachine
    ) {
        $this->base_url = 'https://tokenized.pay.bka.sh/v1.2.0-beta';
        $this->app_key = '';
        $this->app_secret = '';
        $this->username = '';
        $this->password = '';
    }

    public function pay(Request $request)
    {
        if ($request->filled('order_id')) {
            return $this->create($request);
        }

        abort(404);
    }

    public function create(Request $request)
    {
        $request->validate([
            'order_id' => 'required|integer|exists:orders,id',
        ]);

        $order = Order::whereKey((int) $request->order_id)->firstOrFail();
        $payment = Payment::where('order_id', $order->id)->first();
        if (! $payment || $payment->payment_status === 'paid') {
            abort(422, 'Order is not payable.');
        }

        $amountMajor = Money::toMajorInt((int) ($order->amount_minor ?? Money::fromMajor((float) $order->amount)));
        $callbackUrl = route('url-callback', ['orderId' => $order->id], true);

        $header = $this->authHeaders();
        $bodyData = [
            'mode' => '0011',
            'payerReference' => ' ',
            'callbackURL' => $callbackUrl,
            'amount' => $amountMajor,
            'currency' => 'BDT',
            'intent' => 'sale',
            'merchantInvoiceNumber' => 'Inv'.Str::random(10),
        ];

        $response = $this->curlWithBody('/tokenized/checkout/create', $header, 'POST', json_encode($bodyData));
        $decoded = json_decode((string) $response, true);
        if (! $decoded || empty($decoded['paymentID']) || empty($decoded['bkashURL'])) {
            abort(422, 'Unable to initialize bKash checkout.');
        }

        Session::put('paymentID', $decoded['paymentID']);

        return redirect((string) $decoded['bkashURL']);
    }

    public function callback(Request $request)
    {
        $result = $this->processCallback($request);
        $redirectUrl = $result['redirect_url'] ?? route('home');

        if ($result['status'] === 'accepted' || $result['status'] === 'duplicate_ignored') {
            if ($result['status'] === 'accepted') {
                Toastr::success('Thanks, your bKash payment was successfully processed.', 'Success!');
            } else {
                Toastr::success('Your payment event was already processed.', 'Info');
            }
            Cart::instance('shopping')->destroy();

            return redirect($redirectUrl);
        }

        Toastr::error('Unable to confirm bKash payment. Please contact support with your order ID.', 'Failed!');

        return redirect($redirectUrl);
    }

    public function webhook(Request $request)
    {
        $signatureCheck = $this->verificationService->verifyWebhookSignature($request, 'bkash');
        if (! $signatureCheck['valid']) {
            Log::warning('bKash webhook signature validation failed', [
                'reason' => $signatureCheck['reason'],
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'status' => 'rejected',
                'message' => $signatureCheck['reason'],
                'order_id' => null,
            ], 400);
        }

        $result = $this->processCallback($request);
        $httpCode = match ($result['status']) {
            'accepted', 'duplicate_ignored' => 200,
            'mismatch_rejected' => 422,
            default => 400,
        };

        return response()->json([
            'status' => $result['status'],
            'message' => $result['reason'] ?? $result['status'],
            'order_id' => $result['order_id'] ?? null,
        ], $httpCode);
    }

    public function execute(string $paymentID): string
    {
        $header = $this->authHeaders();

        return $this->curlWithBody(
            '/tokenized/checkout/execute',
            $header,
            'POST',
            json_encode(['paymentID' => $paymentID])
        );
    }

    public function query(string $paymentID): string
    {
        $header = $this->authHeaders();

        return $this->curlWithBody(
            '/tokenized/checkout/payment/status',
            $header,
            'POST',
            json_encode(['paymentID' => $paymentID])
        );
    }

    public function getRefund(Request $request)
    {
        return view('CheckoutURL.refund');
    }

    public function refund(Request $request)
    {
        $header = $this->authHeaders();
        $bodyData = [
            'paymentID' => $request->paymentID,
            'amount' => $request->amount,
            'trxID' => $request->trxID,
            'sku' => 'sku',
            'reason' => 'Quality issue',
        ];

        $response = $this->curlWithBody('/tokenized/checkout/payment/refund', $header, 'POST', json_encode($bodyData));

        return view('CheckoutURL.refund')->with(['response' => $response]);
    }

    public function getRefundStatus(Request $request)
    {
        return view('CheckoutURL.refund-status');
    }

    public function refundStatus(Request $request)
    {
        Session::forget('bkash_token');
        Session::put('bkash_token', $this->grant());

        $header = $this->authHeaders();
        $bodyData = [
            'paymentID' => $request->paymentID,
            'trxID' => $request->trxID,
        ];

        $response = $this->curlWithBody('/tokenized/checkout/payment/refund', $header, 'POST', json_encode($bodyData));

        return view('CheckoutURL.refund-status')->with(['response' => $response]);
    }

    private function authHeaders(): array
    {
        $this->ensureGatewayConfigLoaded();

        return [
            'Content-Type:application/json',
            'Authorization:'.$this->grant(),
            'X-APP-Key:'.$this->app_key,
        ];
    }

    private function curlWithBody(string $url, array $header, string $method, string $bodyDataJson): string
    {
        $curl = curl_init($this->base_url.$url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $bodyDataJson);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
        $response = curl_exec($curl);
        curl_close($curl);

        return (string) $response;
    }

    private function grant(): string
    {
        $this->ensureGatewayConfigLoaded();

        $header = [
            'Content-Type:application/json',
            'username:'.$this->username,
            'password:'.$this->password,
        ];

        $bodyData = [
            'app_key' => $this->app_key,
            'app_secret' => $this->app_secret,
        ];

        $response = $this->curlWithBody('/tokenized/checkout/token/grant', $header, 'POST', json_encode($bodyData));
        $decoded = json_decode($response);

        return (string) ($decoded->id_token ?? '');
    }

    private function ensureGatewayConfigLoaded(): void
    {
        if ($this->gatewayConfigLoaded) {
            return;
        }

        $this->gatewayConfigLoaded = true;

        try {
            $bkashGateway = PaymentGateway::query()
                ->where(['status' => 1, 'type' => 'bkash'])
                ->first();

            if (! $bkashGateway) {
                return;
            }

            $this->base_url = (string) $bkashGateway->base_url;
            $this->app_key = (string) $bkashGateway->app_key;
            $this->app_secret = (string) $bkashGateway->app_secret;
            $this->username = (string) $bkashGateway->username;
            $this->password = (string) $bkashGateway->password;
        } catch (Throwable $e) {
            // Keep safe defaults so route:list/config cache can run without DB access.
            Log::warning('Unable to load bKash gateway config from database', [
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @return array{status:string,reason?:string,order_id?:int,redirect_url?:string}
     */
    private function processCallback(Request $request): array
    {
        $payload = $request->all();
        $orderId = (int) ($payload['orderId'] ?? $payload['order_id'] ?? 0);
        if ($orderId <= 0) {
            return ['status' => 'rejected', 'reason' => 'missing_order_id'];
        }

        $order = Order::find($orderId);
        if (! $order) {
            return ['status' => 'rejected', 'reason' => 'order_not_found', 'order_id' => $orderId];
        }
        $redirectUrl = route('customer.order_success', ['id' => $order->id, 't' => $order->order_public_token]);

        $eventKey = (string) ($payload['paymentID'] ?? $payload['trxID'] ?? ('bkash-'.$orderId.'-'.hash('sha256', json_encode($payload))));
        $gatewayPaymentId = (string) ($payload['paymentID'] ?? '');

        $incomingEvent = $this->eventRecorder->recordIncoming(
            'bkash',
            $eventKey,
            $gatewayPaymentId ?: null,
            $order->id,
            $payload,
            false,
            0,
            'BDT',
            (int) ($order->branch_id ?? 0) ?: null
        );

        if ($this->idempotencyService->isDuplicateProcessed($incomingEvent)) {
            return [
                'status' => 'duplicate_ignored',
                'reason' => 'already_processed',
                'order_id' => $order->id,
                'redirect_url' => $redirectUrl,
            ];
        }

        $claimedEvent = $this->idempotencyService->claimForProcessing($incomingEvent);
        if (! $claimedEvent) {
            $freshEvent = $this->idempotencyService->refresh($incomingEvent);
            if ($this->idempotencyService->isDuplicateProcessed($freshEvent)) {
                return [
                    'status' => 'duplicate_ignored',
                    'reason' => 'already_processed',
                    'order_id' => $order->id,
                    'redirect_url' => $redirectUrl,
                ];
            }

            return [
                'status' => 'rejected',
                'reason' => 'processing_in_progress',
                'order_id' => $order->id,
                'redirect_url' => $redirectUrl,
            ];
        }

        $incomingEvent = $claimedEvent;

        try {
            if (in_array(strtolower((string) ($payload['status'] ?? '')), ['failure', 'cancel'], true)) {
                $this->eventRecorder->markProcessed($incomingEvent, 'rejected', 'gateway_status_'.strtolower((string) $payload['status']));

                return [
                    'status' => 'rejected',
                    'reason' => 'gateway_failure',
                    'order_id' => $order->id,
                    'redirect_url' => $redirectUrl,
                ];
            }

            $paymentId = (string) ($payload['paymentID'] ?? '');
            if ($paymentId === '') {
                $this->eventRecorder->markProcessed($incomingEvent, 'rejected', 'missing_payment_id');

                return ['status' => 'rejected', 'reason' => 'missing_payment_id', 'order_id' => $order->id, 'redirect_url' => $redirectUrl];
            }

            $executeResponse = json_decode($this->execute($paymentId), true) ?: [];
            if (($executeResponse['statusCode'] ?? null) !== '0000') {
                $this->eventRecorder->markProcessed($incomingEvent, 'rejected', 'execute_failed');

                return ['status' => 'rejected', 'reason' => 'execute_failed', 'order_id' => $order->id, 'redirect_url' => $redirectUrl];
            }

            $queryResponse = json_decode($this->query($paymentId), true) ?: [];
            $verification = $this->verificationService->verifyBkash($order, $queryResponse);

            $incomingEvent->amount_minor_reported = (int) $verification['reported_amount_minor'];
            $incomingEvent->currency_reported = (string) $verification['currency'];
            $incomingEvent->signature_valid = (bool) $verification['signature_valid'];
            $incomingEvent->gateway_payment_id = $paymentId;
            $incomingEvent->payload = $queryResponse;
            $incomingEvent->save();

            if (! $verification['valid']) {
                Log::warning('bKash payment mismatch rejected', [
                    'order_id' => $order->id,
                    'reason' => $verification['reason'],
                    'payment_id' => $paymentId,
                ]);

                $this->eventRecorder->markProcessed($incomingEvent, 'mismatch_rejected', (string) $verification['reason']);

                return [
                    'status' => 'mismatch_rejected',
                    'reason' => (string) $verification['reason'],
                    'order_id' => $order->id,
                    'redirect_url' => $redirectUrl,
                ];
            }

            DB::transaction(function () use ($order, $paymentId) {
                $lockedOrder = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
                $payment = Payment::where('order_id', $lockedOrder->id)->lockForUpdate()->first();
                if (! $payment) {
                    $payment = new Payment;
                    $payment->order_id = $lockedOrder->id;
                    $payment->customer_id = $lockedOrder->customer_id;
                }

                if ($payment->payment_status !== 'paid') {
                    $this->orderStateMachine->transition($lockedOrder, 2, [
                        'actor_type' => 'gateway',
                        'source' => 'bkash_callback',
                        'reason' => 'bKash payment verified',
                    ]);
                }

                $payment->payment_method = 'bkash';
                $payment->gateway = 'bkash';
                $payment->trx_id = $paymentId;
                $payment->gateway_payment_id = $paymentId;
                $payment->amount = Money::toMajorInt((int) ($lockedOrder->amount_minor ?? 0));
                $payment->amount_minor = (int) ($lockedOrder->amount_minor ?? 0);
                $payment->currency = (string) ($lockedOrder->currency ?? 'BDT');
                $payment->payment_status = 'paid';
                $payment->save();
            });

            $this->eventRecorder->markProcessed($incomingEvent, 'accepted', 'verified');

            return [
                'status' => 'accepted',
                'reason' => 'verified',
                'order_id' => $order->id,
                'redirect_url' => $redirectUrl,
            ];
        } catch (Throwable $e) {
            report($e);
            Log::error('bKash callback processing exception', [
                'order_id' => $order->id,
                'message' => $e->getMessage(),
            ]);
            try {
                $this->eventRecorder->markProcessed($incomingEvent, 'rejected', 'processing_exception');
            } catch (Throwable $markingError) {
                report($markingError);
            }

            return [
                'status' => 'rejected',
                'reason' => 'processing_exception',
                'order_id' => $order->id,
                'redirect_url' => $redirectUrl,
            ];
        }
    }
}
