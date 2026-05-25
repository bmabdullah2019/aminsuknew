<?php

namespace App\Services;

use App\Domain\Orders\OrderStateMachine;
use App\Models\GeneralSetting;
use App\Models\Order;
use App\Models\OrderOtp;
use App\Models\OrderStatus;
use App\Models\Payment;
use App\Models\Shipping;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderConfirmationOtpService
{
    private const PURPOSE = 'order_confirmation';

    private const OTP_TTL_MINUTES = 3;

    private const MAX_ATTEMPTS = 5;

    public function __construct(
        private readonly SmsSenderService $smsSender,
        private readonly OrderStateMachine $orderStateMachine
    ) {}

    public function sendForOrder(Order $order, ?string $ipAddress = null, ?string $userAgent = null): bool
    {
        $order->loadMissing('shipping', 'payment');
        $phone = $this->resolveOrderPhone($order);
        if ($phone === '') {
            throw ValidationException::withMessages([
                'phone' => 'Order shipping phone number is missing.',
            ]);
        }

        if (! $this->isOtpConfirmationAllowed($order)) {
            return false;
        }

        $otp = (string) random_int(100000, 999999);

        DB::transaction(function () use ($order, $phone, $otp, $ipAddress, $userAgent) {
            OrderOtp::query()
                ->where('order_id', (int) $order->id)
                ->where('purpose', self::PURPOSE)
                ->whereNull('verified_at')
                ->delete();

            OrderOtp::create([
                'order_id' => (int) $order->id,
                'phone' => $this->smsSender->normalizeBangladeshPhone($phone),
                'purpose' => self::PURPOSE,
                'otp_hash' => $this->hashOtp($otp),
                'attempts_count' => 0,
                'resend_count' => 0,
                'last_sent_at' => now(),
                'expires_at' => now()->addMinutes(self::OTP_TTL_MINUTES),
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
            ]);
        });

        $siteName = GeneralSetting::where('status', 1)->value('name') ?: config('app.name');
        $message = "Your {$siteName} order {$order->invoice_id} confirmation OTP is {$otp}. Valid for ".self::OTP_TTL_MINUTES.' minutes.';

        return $this->smsSender->send($phone, $message);
    }

    public function verify(Order $order, string $otp, ?string $ipAddress = null, ?string $userAgent = null): Order
    {
        return DB::transaction(function () use ($order, $otp, $ipAddress, $userAgent) {
            $lockedOrder = Order::query()
                ->with('payment')
                ->whereKey((int) $order->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $this->isOtpConfirmationAllowed($lockedOrder, true)) {
                throw ValidationException::withMessages([
                    'otp' => 'This order cannot be confirmed by OTP.',
                ]);
            }

            $orderOtp = OrderOtp::query()
                ->where('order_id', (int) $lockedOrder->id)
                ->where('purpose', self::PURPOSE)
                ->whereNull('verified_at')
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if (! $orderOtp) {
                throw ValidationException::withMessages([
                    'otp' => 'No active OTP was found for this order.',
                ]);
            }

            if ($orderOtp->expires_at->isPast()) {
                throw ValidationException::withMessages([
                    'otp' => 'OTP expired. Please request a new confirmation OTP.',
                ]);
            }

            if ((int) $orderOtp->attempts_count >= self::MAX_ATTEMPTS) {
                throw ValidationException::withMessages([
                    'otp' => 'Too many invalid OTP attempts. Please contact support.',
                ]);
            }

            if (! hash_equals((string) $orderOtp->otp_hash, $this->hashOtp($otp))) {
                $orderOtp->increment('attempts_count');

                throw ValidationException::withMessages([
                    'otp' => 'Invalid OTP.',
                ]);
            }

            $orderOtp->verified_at = now();
            $orderOtp->ip_address = $ipAddress;
            $orderOtp->user_agent = $userAgent;
            $orderOtp->save();

            $confirmedStatusId = $this->confirmedStatusId();
            if ($confirmedStatusId <= 0) {
                throw ValidationException::withMessages([
                    'order_status' => 'Confirmed order status is not configured.',
                ]);
            }

            $this->orderStateMachine->transition($lockedOrder, $confirmedStatusId, [
                'actor_type' => 'customer',
                'actor_id' => (int) ($lockedOrder->customer_id ?? 0) ?: null,
                'source' => 'order_confirmation_otp',
                'reason' => 'Customer confirmed order by SMS OTP',
                'meta' => [
                    'phone' => $orderOtp->phone,
                    'otp_id' => (int) $orderOtp->id,
                ],
            ]);

            return $lockedOrder->fresh(['shipping', 'payment', 'status', 'orderdetails']);
        });
    }

    public function hasActiveOtp(Order $order): bool
    {
        return OrderOtp::query()
            ->where('order_id', (int) $order->id)
            ->where('purpose', self::PURPOSE)
            ->whereNull('verified_at')
            ->where('expires_at', '>', now())
            ->exists();
    }

    public function isOtpConfirmationAllowed(Order $order, bool $strictPayment = false): bool
    {
        if ((int) $order->order_status !== 1) {
            return false;
        }

        $payment = $order->relationLoaded('payment') ? $order->payment : Payment::where('order_id', (int) $order->id)->first();
        $gateway = strtolower((string) ($payment?->gateway ?: $payment?->payment_method));
        $paymentStatus = strtolower((string) ($payment?->payment_status ?? 'pending'));

        if ($gateway !== '' && ! in_array($gateway, ['cod', 'cash on delivery'], true)) {
            return ! $strictPayment || $paymentStatus === 'paid';
        }

        return true;
    }

    private function resolveOrderPhone(Order $order): string
    {
        $shipping = $order->relationLoaded('shipping') ? $order->shipping : Shipping::where('order_id', (int) $order->id)->first();

        return trim((string) ($shipping?->phone ?? ''));
    }

    private function confirmedStatusId(): int
    {
        return (int) (OrderStatus::query()
            ->where('slug', 'confirmed')
            ->orWhere('name', 'Confirmed')
            ->value('id') ?? 2);
    }

    private function hashOtp(string $otp): string
    {
        return hash_hmac('sha256', $otp, (string) config('app.key', 'order-confirmation-otp'));
    }
}
