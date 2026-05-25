<?php

namespace App\Domain\Payments;

use App\Models\Order;
use App\Support\Money;
use Illuminate\Http\Request;

class PaymentVerificationService
{
    public function expectedAmountMinor(Order $order): int
    {
        $amountMinor = (int) ($order->amount_minor ?? 0);
        if ($amountMinor > 0) {
            return $amountMinor;
        }

        return Money::fromMajor((float) $order->amount);
    }

    /**
     * @return array{valid:bool,reason:string}
     */
    public function verifyWebhookSignature(Request $request, string $gateway): array
    {
        $config = (array) config("payments.webhooks.{$gateway}", []);
        $secret = (string) ($config['signature_secret'] ?? '');
        $allowUnsignedWithoutSecret = (bool) config('payments.webhooks.allow_unsigned_when_secret_missing', false);

        if ($secret === '') {
            if ($allowUnsignedWithoutSecret) {
                return [
                    'valid' => true,
                    'reason' => 'signature_not_configured',
                ];
            }

            return [
                'valid' => false,
                'reason' => 'signature_secret_missing',
            ];
        }

        $signatureHeader = (string) ($config['signature_header'] ?? 'X-Webhook-Signature');
        $timestampHeader = (string) ($config['timestamp_header'] ?? 'X-Webhook-Timestamp');

        $providedSignature = (string) $request->header($signatureHeader, '');
        $timestampRaw = (string) $request->header($timestampHeader, '');

        if ($providedSignature === '' || $timestampRaw === '') {
            return [
                'valid' => false,
                'reason' => 'missing_signature_headers',
            ];
        }

        if (! ctype_digit($timestampRaw)) {
            return [
                'valid' => false,
                'reason' => 'invalid_signature_timestamp',
            ];
        }

        $maxSkew = (int) config('payments.webhooks.allowed_clock_skew_seconds', 300);
        $timestamp = (int) $timestampRaw;
        if (abs(now()->timestamp - $timestamp) > max(30, $maxSkew)) {
            return [
                'valid' => false,
                'reason' => 'signature_timestamp_expired',
            ];
        }

        $normalizedSignature = $providedSignature;
        if (str_contains($normalizedSignature, '=')) {
            $parts = explode('=', $normalizedSignature, 2);
            $normalizedSignature = (string) ($parts[1] ?? '');
        }

        $payload = (string) $request->getContent();
        $expectedSignature = hash_hmac('sha256', $timestampRaw.'.'.$payload, $secret);

        if (! hash_equals($expectedSignature, trim($normalizedSignature))) {
            return [
                'valid' => false,
                'reason' => 'signature_mismatch',
            ];
        }

        return [
            'valid' => true,
            'reason' => 'signature_verified',
        ];
    }

    /**
     * @return array{valid:bool,reason:string,reported_amount_minor:int,currency:string,signature_valid:bool}
     */
    public function verifyBkash(Order $order, array $statusPayload): array
    {
        $reportedMinor = $this->parseMinor((string) ($statusPayload['amount'] ?? '0'));
        $expectedMinor = $this->expectedAmountMinor($order);
        $currency = strtoupper((string) ($statusPayload['currency'] ?? 'BDT'));
        $statusCode = (string) ($statusPayload['statusCode'] ?? '');
        $transactionStatus = strtoupper((string) ($statusPayload['transactionStatus'] ?? ''));

        if ($statusCode !== '0000') {
            return [
                'valid' => false,
                'reason' => 'bkash_status_not_success',
                'reported_amount_minor' => $reportedMinor,
                'currency' => $currency,
                'signature_valid' => false,
            ];
        }

        if ($currency !== 'BDT') {
            return [
                'valid' => false,
                'reason' => 'currency_mismatch',
                'reported_amount_minor' => $reportedMinor,
                'currency' => $currency,
                'signature_valid' => false,
            ];
        }

        if ($reportedMinor !== $expectedMinor) {
            return [
                'valid' => false,
                'reason' => 'amount_mismatch',
                'reported_amount_minor' => $reportedMinor,
                'currency' => $currency,
                'signature_valid' => false,
            ];
        }

        if ($transactionStatus !== '' && $transactionStatus !== 'COMPLETED') {
            return [
                'valid' => false,
                'reason' => 'bkash_transaction_not_completed',
                'reported_amount_minor' => $reportedMinor,
                'currency' => $currency,
                'signature_valid' => false,
            ];
        }

        return [
            'valid' => true,
            'reason' => 'accepted',
            'reported_amount_minor' => $reportedMinor,
            'currency' => $currency,
            // bKash callback does not provide a verifiable signature in this integration.
            'signature_valid' => true,
        ];
    }

    /**
     * @return array{valid:bool,reason:string,reported_amount_minor:int,currency:string,signature_valid:bool}
     */
    public function verifyShurjopay(Order $order, object $payload): array
    {
        $spCode = (int) ($payload->sp_code ?? 0);
        $reportedMinor = $this->parseMinor((string) ($payload->amount ?? '0'));
        $expectedMinor = $this->expectedAmountMinor($order);
        $currency = strtoupper((string) ($payload->currency ?? 'BDT'));

        if ($spCode !== 1000) {
            return [
                'valid' => false,
                'reason' => 'shurjopay_not_success',
                'reported_amount_minor' => $reportedMinor,
                'currency' => $currency,
                'signature_valid' => false,
            ];
        }

        if ($currency !== 'BDT') {
            return [
                'valid' => false,
                'reason' => 'currency_mismatch',
                'reported_amount_minor' => $reportedMinor,
                'currency' => $currency,
                'signature_valid' => false,
            ];
        }

        if ($reportedMinor !== $expectedMinor) {
            return [
                'valid' => false,
                'reason' => 'amount_mismatch',
                'reported_amount_minor' => $reportedMinor,
                'currency' => $currency,
                'signature_valid' => false,
            ];
        }

        return [
            'valid' => true,
            'reason' => 'accepted',
            'reported_amount_minor' => $reportedMinor,
            'currency' => $currency,
            // Package-based verify() call is used as trust root here.
            'signature_valid' => true,
        ];
    }

    private function parseMinor(string $rawAmount): int
    {
        $normalized = trim(str_replace(',', '', $rawAmount));
        if ($normalized === '') {
            return 0;
        }

        if (! is_numeric($normalized)) {
            return 0;
        }

        return Money::fromMajor((float) $normalized);
    }
}
