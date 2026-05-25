<?php

namespace App\Http\Controllers\Frontend;

use App\Domain\Orders\OrderStateMachine;
use App\Domain\Payments\PaymentEventRecorder;
use App\Domain\Payments\PaymentIdempotencyService;
use App\Domain\Payments\PaymentVerificationService;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Support\Money;
use Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use shurjopayv2\ShurjopayLaravelPackage8\Http\Controllers\ShurjopayController;
use Throwable;
use Toastr;

class ShurjopayControllers extends Controller
{
    public function __construct(
        private readonly PaymentEventRecorder $eventRecorder,
        private readonly PaymentIdempotencyService $idempotencyService,
        private readonly PaymentVerificationService $verificationService,
        private readonly OrderStateMachine $orderStateMachine
    ) {}

    public function payment_success(Request $request)
    {
        $result = $this->processGatewayResult((string) $request->query('order_id', ''));

        if ($result['status'] === 'accepted' || $result['status'] === 'duplicate_ignored') {
            Cart::instance('shopping')->destroy();
            Toastr::success('Thanks, your payment has been processed.', 'Success!');

            return redirect($result['redirect_url'] ?? route('home'));
        }

        Toastr::error('Your payment could not be verified.', 'Failed');

        return redirect($result['redirect_url'] ?? route('customer.checkout'));
    }

    public function webhook(Request $request)
    {
        $signatureCheck = $this->verificationService->verifyWebhookSignature($request, 'shurjopay');
        if (! $signatureCheck['valid']) {
            Log::warning('ShurjoPay webhook signature validation failed', [
                'reason' => $signatureCheck['reason'],
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'status' => 'rejected',
                'message' => $signatureCheck['reason'],
                'order_id' => null,
            ], 400);
        }

        $result = $this->processGatewayResult((string) $request->input('order_id', ''));
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

    public function payment_cancel(Request $request)
    {
        Toastr::error('Your payment cancelled', 'Cancelled!');

        return redirect()->route('home');
    }

    /**
     * @return array{status:string,reason?:string,order_id?:int,redirect_url?:string}
     */
    private function processGatewayResult(string $gatewayOrderReference): array
    {
        if ($gatewayOrderReference === '') {
            return ['status' => 'rejected', 'reason' => 'missing_order_reference'];
        }

        $shurjopay = new ShurjopayController;
        $json = $shurjopay->verify($gatewayOrderReference);
        $data = json_decode((string) $json);
        $payload = (array) ($data[0] ?? []);

        $orderId = (int) ($payload['value1'] ?? $payload['id'] ?? 0);
        if ($orderId <= 0) {
            return ['status' => 'rejected', 'reason' => 'missing_order_id'];
        }

        $order = Order::find($orderId);
        if (! $order) {
            return ['status' => 'rejected', 'reason' => 'order_not_found', 'order_id' => $orderId];
        }
        $redirectUrl = route('customer.order_success', ['id' => $order->id, 't' => $order->order_public_token]);

        $eventKey = (string) ($payload['bank_trx_id'] ?? ('shurjopay-'.$gatewayOrderReference));
        $incomingEvent = $this->eventRecorder->recordIncoming(
            'shurjopay',
            $eventKey,
            (string) ($payload['bank_trx_id'] ?? '') ?: null,
            $order->id,
            $payload,
            false,
            0,
            strtoupper((string) ($payload['currency'] ?? 'BDT')),
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
            $verification = $this->verificationService->verifyShurjopay($order, (object) $payload);
            $incomingEvent->signature_valid = (bool) $verification['signature_valid'];
            $incomingEvent->amount_minor_reported = (int) $verification['reported_amount_minor'];
            $incomingEvent->currency_reported = (string) $verification['currency'];
            $incomingEvent->save();

            if (! $verification['valid']) {
                Log::warning('ShurjoPay payment mismatch rejected', [
                    'order_id' => $order->id,
                    'reason' => $verification['reason'],
                    'gateway_reference' => $gatewayOrderReference,
                ]);

                $this->eventRecorder->markProcessed($incomingEvent, 'mismatch_rejected', (string) $verification['reason']);

                return [
                    'status' => 'mismatch_rejected',
                    'reason' => (string) $verification['reason'],
                    'order_id' => $order->id,
                    'redirect_url' => $redirectUrl,
                ];
            }

            DB::transaction(function () use ($order, $payload) {
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
                        'source' => 'shurjopay_callback',
                        'reason' => 'ShurjoPay payment verified',
                    ]);
                }

                $payment->payment_method = (string) ($payload['method'] ?? 'shurjopay');
                $payment->gateway = 'shurjopay';
                $payment->trx_id = (string) ($payload['bank_trx_id'] ?? null);
                $payment->gateway_payment_id = (string) ($payload['bank_trx_id'] ?? null);
                $payment->sender_number = (string) ($payload['phone_no'] ?? null);
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
            Log::error('ShurjoPay callback processing exception', [
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
