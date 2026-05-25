<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\PhoneBlock;
use App\Models\Shipping;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class PhoneBlockService
{
    private const DEFAULT_CANCEL_THRESHOLD = 3;

    public function autoBlockAfterCancellation(Order $order): ?PhoneBlock
    {
        if (! $this->isAutoBlockEnabled() || ! $this->hasRequiredTablesForAutoBlock()) {
            return null;
        }

        $phone = $this->resolveOrderPhone($order);
        if ($phone === '') {
            return null;
        }

        $cancelCount = $this->countCancelledOrdersForPhone($phone);
        if ($cancelCount < $this->cancelThreshold()) {
            return null;
        }

        $normalizedPhone = $this->normalizePhone($phone);
        if ($normalizedPhone === '') {
            return null;
        }

        $block = PhoneBlock::query()->firstOrNew([
            'normalized_phone' => $normalizedPhone,
        ]);

        $block->phone = trim($phone);
        $block->cancel_count = $cancelCount;
        $block->is_active = true;
        $block->blocked_source = 'auto_cancel_threshold';
        $block->blocked_by_order_id = (int) $order->id ?: null;
        $block->reason = 'Auto blocked after '.$cancelCount.' cancelled orders.';
        $block->blocked_at = now();
        $block->save();

        return $block;
    }

    public function isPhoneBlocked(?string $phone): bool
    {
        return $this->getActiveBlockForPhone($phone) !== null;
    }

    public function getActiveBlockForPhone(?string $phone): ?PhoneBlock
    {
        if (! Schema::hasTable('phone_blocks')) {
            return null;
        }

        $rawPhone = trim((string) $phone);
        $normalized = $this->normalizePhone($rawPhone);
        if ($normalized === '') {
            return null;
        }

        $normalizedCandidates = $this->normalizedCandidates($normalized);

        return PhoneBlock::query()
            ->active()
            ->where(function ($query) use ($rawPhone, $normalizedCandidates) {
                $query->whereIn('normalized_phone', $normalizedCandidates);

                if ($rawPhone !== '') {
                    $query->orWhere('phone', $rawPhone);
                }
            })
            ->latest('id')
            ->first();
    }

    public function normalizePhone(?string $phone): string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone) ?? '';
        if ($digits === '') {
            return '';
        }

        // Bangladesh canonical forms:
        // 8801XXXXXXXXX / 881XXXXXXXXX / 1XXXXXXXXX => 01XXXXXXXXX
        if (str_starts_with($digits, '880') && strlen($digits) === 13) {
            return '0'.substr($digits, 3);
        }

        if (str_starts_with($digits, '88') && strlen($digits) === 12) {
            return '0'.substr($digits, 2);
        }

        if (str_starts_with($digits, '1') && strlen($digits) === 10) {
            return '0'.$digits;
        }

        return $digits;
    }

    public function manualBlock(string $phone, string $reason): ?PhoneBlock
    {
        if (! Schema::hasTable('phone_blocks')) {
            return null;
        }

        $rawPhone = trim($phone);
        $normalized = $this->normalizePhone($rawPhone);
        if ($normalized === '') {
            return null;
        }

        $block = PhoneBlock::query()->firstOrNew([
            'normalized_phone' => $normalized,
        ]);

        $block->phone = $rawPhone;
        $block->is_active = true;
        $block->blocked_source = 'manual_admin';
        $block->reason = trim($reason) !== '' ? trim($reason) : 'Manually blocked by admin.';
        $block->blocked_at = now();
        $block->save();

        return $block;
    }

    public function unblock(PhoneBlock $phoneBlock): void
    {
        $phoneBlock->is_active = false;
        $phoneBlock->save();
    }

    private function cancelThreshold(): int
    {
        $threshold = (int) config('features.orders.phone_cancel_block_threshold', self::DEFAULT_CANCEL_THRESHOLD);

        return max(1, $threshold);
    }

    private function isAutoBlockEnabled(): bool
    {
        return (bool) config('features.orders.phone_cancel_auto_block_enabled', true);
    }

    private function hasRequiredTablesForAutoBlock(): bool
    {
        return Schema::hasTable('phone_blocks')
            && Schema::hasTable('orders')
            && Schema::hasTable('shippings');
    }

    private function resolveOrderPhone(Order $order): string
    {
        try {
            if ($order->relationLoaded('shipping') && $order->shipping) {
                return trim((string) $order->shipping->phone);
            }

            if ((int) $order->id > 0 && Schema::hasTable('shippings')) {
                $shippingPhone = (string) (Shipping::query()
                    ->where('order_id', (int) $order->id)
                    ->value('phone') ?? '');

                if (trim($shippingPhone) !== '') {
                    return trim($shippingPhone);
                }
            }

            if ($order->relationLoaded('customer') && $order->customer) {
                return trim((string) $order->customer->phone);
            }
        } catch (Throwable $exception) {
            Log::warning('Failed to resolve phone for order cancellation block check', [
                'order_id' => (int) $order->id,
                'message' => $exception->getMessage(),
            ]);
        }

        return '';
    }

    private function countCancelledOrdersForPhone(string $phone): int
    {
        $normalized = $this->normalizePhone($phone);
        if ($normalized === '') {
            return 0;
        }

        $statusIds = $this->cancellationStatusIds();
        if (empty($statusIds)) {
            return 0;
        }

        $normalizedCandidates = $this->normalizedCandidates($normalized);
        $normalizedSql = $this->normalizedSqlExpression('shippings.phone');
        $rawPhone = trim($phone);

        try {
            return (int) Order::query()
                ->join('shippings', 'shippings.order_id', '=', 'orders.id')
                ->whereIn('orders.order_status', $statusIds)
                ->where(function ($query) use ($rawPhone, $normalizedCandidates, $normalizedSql) {
                    if ($rawPhone !== '') {
                        $query->where('shippings.phone', $rawPhone);
                    }

                    if (! empty($normalizedCandidates)) {
                        $placeholders = implode(',', array_fill(0, count($normalizedCandidates), '?'));
                        $method = $rawPhone !== '' ? 'orWhereRaw' : 'whereRaw';
                        $query->{$method}($normalizedSql.' IN ('.$placeholders.')', $normalizedCandidates);
                    }
                })
                ->count(DB::raw('DISTINCT orders.id'));
        } catch (Throwable $exception) {
            Log::warning('Failed to count cancelled orders for phone block check', [
                'phone' => $phone,
                'message' => $exception->getMessage(),
            ]);

            return 0;
        }
    }

    /**
     * @return array<int, int>
     */
    private function cancellationStatusIds(): array
    {
        static $cache = null;

        if (is_array($cache)) {
            return $cache;
        }

        if (! Schema::hasTable('order_statuses')) {
            $cache = [6];

            return $cache;
        }

        try {
            $statuses = OrderStatus::query()
                ->select('id', 'slug', 'name')
                ->get();

            $ids = $statuses
                ->filter(function (OrderStatus $status): bool {
                    if ((int) $status->id === 6) {
                        return true;
                    }

                    $slug = strtolower((string) $status->slug);
                    $name = strtolower((string) $status->name);

                    return str_contains($slug, 'cancel') || str_contains($name, 'cancel');
                })
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->filter(fn (int $id) => $id > 0)
                ->values()
                ->all();

            $cache = ! empty($ids) ? array_values(array_unique($ids)) : [6];

            return $cache;
        } catch (Throwable $exception) {
            Log::warning('Failed to resolve cancellation status ids for phone block check', [
                'message' => $exception->getMessage(),
            ]);
            $cache = [6];

            return $cache;
        }
    }

    /**
     * @return array<int, string>
     */
    private function normalizedCandidates(string $normalized): array
    {
        if ($normalized === '') {
            return [];
        }

        $candidates = [$normalized];

        if (str_starts_with($normalized, '0') && strlen($normalized) === 11) {
            $core = substr($normalized, 1);
            $candidates[] = $core;
            $candidates[] = '88'.$normalized;
            $candidates[] = '880'.$core;
        } elseif (str_starts_with($normalized, '880') && strlen($normalized) === 13) {
            $local = '0'.substr($normalized, 3);
            $candidates[] = $local;
            $candidates[] = substr($local, 1);
            $candidates[] = '88'.$local;
        } elseif (str_starts_with($normalized, '1') && strlen($normalized) === 10) {
            $local = '0'.$normalized;
            $candidates[] = $local;
            $candidates[] = '88'.$local;
            $candidates[] = '880'.$normalized;
        }

        return array_values(array_unique(array_filter($candidates)));
    }

    private function normalizedSqlExpression(string $column): string
    {
        return "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE({$column}, ' ', ''), '-', ''), '+', ''), '(', ''), ')', '')";
    }
}
