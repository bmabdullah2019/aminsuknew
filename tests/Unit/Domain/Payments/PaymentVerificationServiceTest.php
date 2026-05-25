<?php

namespace Tests\Unit\Domain\Payments;

use App\Domain\Payments\PaymentVerificationService;
use App\Models\Order;
use Illuminate\Http\Request;
use Tests\TestCase;

class PaymentVerificationServiceTest extends TestCase
{
    public function test_bkash_verification_accepts_matching_payload(): void
    {
        $service = new PaymentVerificationService;
        $order = new Order;
        $order->amount_minor = 12500;
        $order->amount = 125;

        $result = $service->verifyBkash($order, [
            'statusCode' => '0000',
            'transactionStatus' => 'COMPLETED',
            'currency' => 'BDT',
            'amount' => '125.00',
        ]);

        $this->assertTrue($result['valid']);
        $this->assertSame('accepted', $result['reason']);
        $this->assertSame(12500, $result['reported_amount_minor']);
    }

    public function test_bkash_verification_rejects_amount_mismatch(): void
    {
        $service = new PaymentVerificationService;
        $order = new Order;
        $order->amount_minor = 12500;
        $order->amount = 125;

        $result = $service->verifyBkash($order, [
            'statusCode' => '0000',
            'transactionStatus' => 'COMPLETED',
            'currency' => 'BDT',
            'amount' => '100.00',
        ]);

        $this->assertFalse($result['valid']);
        $this->assertSame('amount_mismatch', $result['reason']);
    }

    public function test_shurjopay_verification_rejects_non_success_code(): void
    {
        $service = new PaymentVerificationService;
        $order = new Order;
        $order->amount_minor = 9500;
        $order->amount = 95;

        $payload = (object) [
            'sp_code' => 999,
            'currency' => 'BDT',
            'amount' => '95.00',
        ];

        $result = $service->verifyShurjopay($order, $payload);

        $this->assertFalse($result['valid']);
        $this->assertSame('shurjopay_not_success', $result['reason']);
    }

    public function test_webhook_signature_is_optional_when_secret_not_configured(): void
    {
        config()->set('payments.webhooks.bkash.signature_secret', '');
        config()->set('payments.webhooks.allow_unsigned_when_secret_missing', true);

        $service = new PaymentVerificationService;
        $request = Request::create('/webhooks/bkash/callback', 'POST', [], [], [], [], '{"ok":true}');

        $result = $service->verifyWebhookSignature($request, 'bkash');

        $this->assertTrue($result['valid']);
        $this->assertSame('signature_not_configured', $result['reason']);
    }

    public function test_webhook_signature_requires_secret_in_strict_mode(): void
    {
        config()->set('payments.webhooks.bkash.signature_secret', '');
        config()->set('payments.webhooks.allow_unsigned_when_secret_missing', false);

        $service = new PaymentVerificationService;
        $request = Request::create('/webhooks/bkash/callback', 'POST', [], [], [], [], '{"ok":true}');

        $result = $service->verifyWebhookSignature($request, 'bkash');

        $this->assertFalse($result['valid']);
        $this->assertSame('signature_secret_missing', $result['reason']);
    }

    public function test_webhook_signature_validation_rejects_invalid_signature(): void
    {
        config()->set('payments.webhooks.bkash.signature_secret', 'test-secret');
        config()->set('payments.webhooks.bkash.signature_header', 'X-Webhook-Signature');
        config()->set('payments.webhooks.bkash.timestamp_header', 'X-Webhook-Timestamp');
        config()->set('payments.webhooks.allowed_clock_skew_seconds', 300);

        $payload = '{"event":"payment_completed"}';
        $timestamp = (string) now()->timestamp;
        $request = Request::create('/webhooks/bkash/callback', 'POST', [], [], [], [], $payload);
        $request->headers->set('X-Webhook-Timestamp', $timestamp);
        $request->headers->set('X-Webhook-Signature', 'sha256=invalid-signature');

        $service = new PaymentVerificationService;
        $result = $service->verifyWebhookSignature($request, 'bkash');

        $this->assertFalse($result['valid']);
        $this->assertSame('signature_mismatch', $result['reason']);
    }

    public function test_webhook_signature_validation_accepts_valid_signature(): void
    {
        config()->set('payments.webhooks.shurjopay.signature_secret', 'another-secret');
        config()->set('payments.webhooks.shurjopay.signature_header', 'X-Webhook-Signature');
        config()->set('payments.webhooks.shurjopay.timestamp_header', 'X-Webhook-Timestamp');
        config()->set('payments.webhooks.allowed_clock_skew_seconds', 300);

        $payload = '{"event":"payment_completed"}';
        $timestamp = (string) now()->timestamp;
        $signature = hash_hmac('sha256', $timestamp.'.'.$payload, 'another-secret');

        $request = Request::create('/webhooks/shurjopay/callback', 'POST', [], [], [], [], $payload);
        $request->headers->set('X-Webhook-Timestamp', $timestamp);
        $request->headers->set('X-Webhook-Signature', 'sha256='.$signature);

        $service = new PaymentVerificationService;
        $result = $service->verifyWebhookSignature($request, 'shurjopay');

        $this->assertTrue($result['valid']);
        $this->assertSame('signature_verified', $result['reason']);
    }
}
