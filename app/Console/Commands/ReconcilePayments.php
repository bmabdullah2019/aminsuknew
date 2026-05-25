<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentEvent;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ReconcilePayments extends Command
{
    /**
     * @var string
     */
    protected $signature = 'payments:reconcile
        {--date= : Reconciliation date (YYYY-MM-DD)}
        {--json : Output machine-readable JSON summary}
        {--max_issues=200 : Maximum issue rows to include in output}';

    /**
     * @var string
     */
    protected $description = 'Reconcile payment records against orders and payment events';

    public function handle(): int
    {
        $json = (bool) $this->option('json');
        $maxIssues = (int) ($this->option('max_issues') ?? 200);
        if ($maxIssues <= 0) {
            return $this->emitError('Invalid --max_issues value. Use a positive integer.', self::INVALID, $json);
        }

        $dateOption = $this->option('date');
        try {
            $date = $dateOption ? Carbon::parse($dateOption)->toDateString() : now()->toDateString();
        } catch (\Throwable $e) {
            return $this->emitError('Invalid --date value. Use YYYY-MM-DD format.', self::INVALID, $json);
        }
        $startOfDay = Carbon::parse($date)->startOfDay();
        $endOfDay = Carbon::parse($date)->endOfDay();

        if (! $json) {
            $this->info("Reconciliation date: {$date}");
        }

        $payments = Payment::query()
            ->whereBetween('created_at', [$startOfDay, $endOfDay])
            ->whereIn('gateway', ['bkash', 'shurjopay', 'cod'])
            ->orderBy('id')
            ->get();

        $mismatches = [];
        foreach ($payments as $payment) {
            $order = Order::query()->find($payment->order_id);
            if (! $order) {
                $mismatches[] = [
                    'payment_id' => (int) $payment->id,
                    'order_id' => (int) $payment->order_id,
                    'issue' => 'missing_order',
                ];

                continue;
            }

            $orderAmountMinor = (int) ($order->amount_minor ?? 0);
            $paymentAmountMinor = (int) ($payment->amount_minor ?? 0);
            $currencyMismatch = strtoupper((string) ($payment->currency ?? 'BDT')) !== strtoupper((string) ($order->currency ?? 'BDT'));
            $amountMismatch = $orderAmountMinor > 0 && $paymentAmountMinor > 0 && $orderAmountMinor !== $paymentAmountMinor;

            if ($currencyMismatch || $amountMismatch) {
                $mismatches[] = [
                    'payment_id' => (int) $payment->id,
                    'order_id' => (int) $order->id,
                    'issue' => $currencyMismatch ? 'currency_mismatch' : 'amount_mismatch',
                ];
            }
        }

        $unresolvedEvents = PaymentEvent::query()
            ->whereBetween('created_at', [$startOfDay, $endOfDay])
            ->whereIn('status', ['mismatch_rejected', 'rejected'])
            ->orderBy('id')
            ->get(['id', 'gateway', 'event_key', 'order_id', 'status', 'status_reason', 'created_at']);

        $duplicateEventsCount = PaymentEvent::query()
            ->whereBetween('created_at', [$startOfDay, $endOfDay])
            ->where('status', 'duplicate_ignored')
            ->count();

        if (! $json) {
            $this->line('');
            $this->info('Summary');
            $this->table(
                ['Metric', 'Value'],
                [
                    ['payments_checked', $payments->count()],
                    ['payment_mismatches', count($mismatches)],
                    ['unresolved_events', $unresolvedEvents->count()],
                    ['duplicate_events_ignored', $duplicateEventsCount],
                ]
            );

            if (! empty($mismatches)) {
                $this->line('');
                $this->warn('Payment mismatches');
                $this->table(['payment_id', 'order_id', 'issue'], array_slice($mismatches, 0, $maxIssues));
            }

            if ($unresolvedEvents->isNotEmpty()) {
                $this->line('');
                $this->warn('Unresolved payment events');
                $this->table(
                    ['id', 'gateway', 'event_key', 'order_id', 'status', 'reason', 'created_at'],
                    $unresolvedEvents->take($maxIssues)->map(function ($event) {
                        return [
                            'id' => (int) $event->id,
                            'gateway' => (string) $event->gateway,
                            'event_key' => (string) $event->event_key,
                            'order_id' => (int) ($event->order_id ?? 0),
                            'status' => (string) $event->status,
                            'reason' => (string) ($event->status_reason ?? ''),
                            'created_at' => (string) $event->created_at,
                        ];
                    })->all()
                );
            }
        }

        if ($json) {
            $unresolvedEventsPayload = $unresolvedEvents->take($maxIssues)->map(function ($event) {
                return [
                    'id' => (int) $event->id,
                    'gateway' => (string) $event->gateway,
                    'event_key' => (string) $event->event_key,
                    'order_id' => (int) ($event->order_id ?? 0),
                    'status' => (string) $event->status,
                    'reason' => (string) ($event->status_reason ?? ''),
                    'created_at' => (string) $event->created_at,
                ];
            })->values()->all();

            $this->line((string) json_encode([
                'ok' => empty($mismatches) && $unresolvedEvents->isEmpty(),
                'date' => $date,
                'metrics' => [
                    'payments_checked' => (int) $payments->count(),
                    'payment_mismatches' => (int) count($mismatches),
                    'unresolved_events' => (int) $unresolvedEvents->count(),
                    'duplicate_events_ignored' => (int) $duplicateEventsCount,
                ],
                'issues_total' => [
                    'mismatches' => (int) count($mismatches),
                    'unresolved_events' => (int) $unresolvedEvents->count(),
                ],
                'issues_sample' => [
                    'mismatches' => array_slice($mismatches, 0, $maxIssues),
                    'unresolved_events' => $unresolvedEventsPayload,
                ],
            ]));
        }

        return self::SUCCESS;
    }

    private function emitError(string $message, int $code, bool $json): int
    {
        if ($json) {
            $this->line((string) json_encode([
                'ok' => false,
                'error' => $message,
            ]));
        } else {
            $this->error($message);
        }

        return $code;
    }
}
