<?php

namespace App\Domain\Payments;

use App\Models\PaymentEvent;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;

class PaymentEventRecorder
{
    public function recordIncoming(
        string $gateway,
        string $eventKey,
        ?string $gatewayPaymentId,
        ?int $orderId,
        array $payload,
        bool $signatureValid,
        int $amountMinorReported,
        string $currencyReported = 'BDT',
        ?int $branchId = null
    ): PaymentEvent {
        $attributes = [
            'gateway' => $gateway,
            'event_key' => $eventKey,
        ];

        $values = [
            'gateway_payment_id' => $gatewayPaymentId,
            'order_id' => $orderId,
            'payload_hash' => hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE)),
            'signature_valid' => $signatureValid,
            'amount_minor_reported' => max(0, $amountMinorReported),
            'currency_reported' => $currencyReported,
            'payload' => $payload,
            'status' => 'received',
        ];
        if ($this->hasBranchColumn()) {
            $values['branch_id'] = $branchId;
        }

        try {
            return PaymentEvent::firstOrCreate($attributes, $values);
        } catch (QueryException $e) {
            // Concurrent insert can race into a unique-key violation.
            return PaymentEvent::where($attributes)->firstOrFail();
        }
    }

    public function markProcessed(PaymentEvent $event, string $status, ?string $reason = null): PaymentEvent
    {
        $event->status = $status;
        $event->status_reason = $reason;
        $event->processed_at = now();
        $event->save();

        return $event;
    }

    private function hasBranchColumn(): bool
    {
        static $hasBranchColumn = null;

        if ($hasBranchColumn === null) {
            $table = (new PaymentEvent)->getTable();
            $hasBranchColumn = Schema::hasTable($table) && Schema::hasColumn($table, 'branch_id');
        }

        return (bool) $hasBranchColumn;
    }
}
