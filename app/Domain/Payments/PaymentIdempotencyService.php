<?php

namespace App\Domain\Payments;

use App\Models\PaymentEvent;

class PaymentIdempotencyService
{
    public function claimForProcessing(PaymentEvent $event, int $staleAfterSeconds = 300): ?PaymentEvent
    {
        $now = now();
        $staleThreshold = $now->copy()->subSeconds(max(30, $staleAfterSeconds));

        $updated = PaymentEvent::query()
            ->whereKey($event->id)
            ->whereNull('processed_at')
            ->where(function ($query) use ($staleThreshold) {
                $query->where('status', 'received')
                    ->orWhere(function ($sub) use ($staleThreshold) {
                        $sub->where('status', 'processing')
                            ->where('updated_at', '<=', $staleThreshold);
                    });
            })
            ->update([
                'status' => 'processing',
                'status_reason' => null,
                'updated_at' => $now,
            ]);

        if ($updated === 0) {
            return null;
        }

        return PaymentEvent::query()->whereKey($event->id)->first();
    }

    public function refresh(PaymentEvent $event): PaymentEvent
    {
        return PaymentEvent::query()->whereKey($event->id)->firstOrFail();
    }

    public function isDuplicateProcessed(PaymentEvent $event): bool
    {
        return $event->processed_at !== null;
    }

    public function isDuplicateAccepted(PaymentEvent $event): bool
    {
        return $this->isDuplicateProcessed($event) && in_array($event->status, ['accepted', 'duplicate_ignored'], true);
    }
}
