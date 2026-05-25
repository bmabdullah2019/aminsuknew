<?php

namespace App\Services;

use App\Models\CourierSettlement;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CourierAccountingService
{
    public function __construct(
        private readonly BranchAccountingService $accountingService
    ) {}

    public function postReceivableForDeliveredOrder(Order $order, ?float $amount = null): void
    {
        $status = strtolower((string) ($order->steadfast_status ?? ''));
        if (! in_array($status, ['delivered', 'partial_delivered'], true)) {
            return;
        }

        $this->accountingService->postCourierReceivableEntry($order, $amount);
    }

    /**
     * @param  array<string,mixed>  $payload
     */
    public function syncSteadfastPayment(array $payload): CourierSettlement
    {
        return DB::transaction(function () use ($payload): CourierSettlement {
            $paymentId = (string) ($payload['id'] ?? $payload['payment_id'] ?? $payload['paymentID'] ?? '');
            if ($paymentId === '') {
                $paymentId = sha1(json_encode($payload));
            }

            $consignments = $this->extractConsignments($payload);
            $settlementDate = $this->resolveDate($payload['created_at'] ?? $payload['date'] ?? $payload['payment_date'] ?? null);
            $receivedAmount = $this->money($payload['amount'] ?? $payload['paid_amount'] ?? $payload['net_amount'] ?? null);

            $totals = [
                'gross_cod_amount' => 0.0,
                'delivery_charge' => 0.0,
                'return_charge' => 0.0,
                'adjustment_amount' => 0.0,
                'net_receivable_amount' => 0.0,
            ];

            foreach ($consignments as $consignment) {
                $line = $this->normalizeConsignment($consignment);
                $totals['gross_cod_amount'] += $line['cod_amount'];
                $totals['delivery_charge'] += $line['delivery_charge'];
                $totals['return_charge'] += $line['return_charge'];
                $totals['adjustment_amount'] += $line['adjustment_amount'];
                $totals['net_receivable_amount'] += $line['net_amount'];
            }

            if ($receivedAmount <= 0) {
                $receivedAmount = round((float) $totals['net_receivable_amount'], 2);
            }

            $settlement = CourierSettlement::query()->updateOrCreate(
                ['courier_type' => 'steadfast', 'courier_payment_id' => $paymentId],
                [
                    'settlement_date' => $settlementDate,
                    'gross_cod_amount' => round((float) $totals['gross_cod_amount'], 2),
                    'delivery_charge' => round((float) $totals['delivery_charge'], 2),
                    'return_charge' => round((float) $totals['return_charge'], 2),
                    'adjustment_amount' => round((float) $totals['adjustment_amount'], 2),
                    'net_receivable_amount' => round((float) $totals['net_receivable_amount'], 2),
                    'received_amount' => round((float) $receivedAmount, 2),
                    'status' => 'synced',
                    'raw_payload' => $payload,
                    'synced_at' => now(),
                ]
            );

            foreach ($consignments as $consignment) {
                $line = $this->normalizeConsignment($consignment);
                $order = $this->resolveOrder($line);

                if ($order) {
                    $this->syncOrderCourierFields($order, $line);
                    $this->postReceivableForDeliveredOrder($order, $line['cod_amount'] > 0 ? $line['cod_amount'] : null);
                }

                if (empty($line['consignment_id']) && empty($line['invoice_id']) && empty($line['tracking_code'])) {
                    continue;
                }

                $identity = $line['consignment_id']
                    ? ['consignment_id' => $line['consignment_id']]
                    : ['invoice_id' => $line['invoice_id'], 'tracking_code' => $line['tracking_code']];

                $settlement->orders()->updateOrCreate(
                    $identity,
                    [
                        'order_id' => $order?->id,
                        'tracking_code' => $line['tracking_code'],
                        'invoice_id' => $line['invoice_id'],
                        'cod_amount' => $line['cod_amount'],
                        'delivery_charge' => $line['delivery_charge'],
                        'return_charge' => $line['return_charge'],
                        'adjustment_amount' => $line['adjustment_amount'],
                        'net_amount' => $line['net_amount'],
                        'delivery_status' => $line['delivery_status'],
                        'raw_payload' => $consignment,
                    ]
                );
            }

            $this->accountingService->postCourierSettlementEntry($settlement);

            return $settlement->load('orders');
        });
    }

    /**
     * @param  array<string,mixed>  $payload
     * @return array<int,array<string,mixed>>
     */
    private function extractConsignments(array $payload): array
    {
        foreach (['consignments', 'orders', 'items', 'data'] as $key) {
            if (isset($payload[$key]) && is_array($payload[$key])) {
                return array_values(array_filter($payload[$key], 'is_array'));
            }
        }

        return [];
    }

    /**
     * @param  array<string,mixed>  $consignment
     * @return array<string,mixed>
     */
    private function normalizeConsignment(array $consignment): array
    {
        $cod = $this->money($consignment['cod_amount'] ?? $consignment['amount'] ?? $consignment['collection_amount'] ?? null);
        $deliveryCharge = $this->money($consignment['delivery_charge'] ?? $consignment['charge'] ?? $consignment['cod_charge'] ?? null);
        $returnCharge = $this->money($consignment['return_charge'] ?? $consignment['return_fee'] ?? null);
        $adjustment = $this->money($consignment['adjustment_amount'] ?? $consignment['adjustment'] ?? null);
        $net = $this->money($consignment['net_amount'] ?? $consignment['payable_amount'] ?? null);

        if ($net <= 0 && $cod > 0) {
            $net = round(max(0, $cod - $deliveryCharge - $returnCharge - $adjustment), 2);
        }

        return [
            'consignment_id' => $this->nullableInt($consignment['consignment_id'] ?? $consignment['cid'] ?? null),
            'tracking_code' => $this->nullableString($consignment['tracking_code'] ?? $consignment['tracking'] ?? null),
            'invoice_id' => $this->nullableString($consignment['invoice'] ?? $consignment['invoice_id'] ?? null),
            'cod_amount' => $cod,
            'delivery_charge' => $deliveryCharge,
            'return_charge' => $returnCharge,
            'adjustment_amount' => $adjustment,
            'net_amount' => $net,
            'delivery_status' => $this->nullableString($consignment['status'] ?? $consignment['delivery_status'] ?? null),
        ];
    }

    /**
     * @param  array<string,mixed>  $line
     */
    private function resolveOrder(array $line): ?Order
    {
        if (empty($line['invoice_id']) && empty($line['consignment_id']) && empty($line['tracking_code'])) {
            return null;
        }

        return Order::query()
            ->when($line['invoice_id'], fn ($query) => $query->orWhere('invoice_id', $line['invoice_id']))
            ->when($line['consignment_id'], fn ($query) => $query->orWhere('steadfast_consignment_id', $line['consignment_id']))
            ->when($line['tracking_code'], fn ($query) => $query->orWhere('steadfast_tracking_code', $line['tracking_code']))
            ->first();
    }

    /**
     * @param  array<string,mixed>  $line
     */
    private function syncOrderCourierFields(Order $order, array $line): void
    {
        $dirty = false;

        if (! empty($line['delivery_status']) && $order->steadfast_status !== $line['delivery_status']) {
            $order->steadfast_status = $line['delivery_status'];
            $dirty = true;
        }

        if (! empty($line['consignment_id']) && empty($order->steadfast_consignment_id)) {
            $order->steadfast_consignment_id = $line['consignment_id'];
            $dirty = true;
        }

        if (! empty($line['tracking_code']) && empty($order->steadfast_tracking_code)) {
            $order->steadfast_tracking_code = $line['tracking_code'];
            $dirty = true;
        }

        if ($dirty) {
            $order->save();
        }
    }

    private function money(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        return round((float) preg_replace('/[^0-9.\-]/', '', (string) $value), 2);
    }

    private function nullableInt(mixed $value): ?int
    {
        return $value === null || $value === '' ? null : (int) $value;
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' ? null : $value;
    }

    private function resolveDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return now()->toDateString();
        }

        return Carbon::parse((string) $value)->toDateString();
    }
}
