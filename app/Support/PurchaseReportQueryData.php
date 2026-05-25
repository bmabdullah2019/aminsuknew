<?php

namespace App\Support;

final class PurchaseReportQueryData
{
    public function __construct(
        public readonly string $keyword,
        public readonly ?int $supplierId,
        public readonly ?int $warehouseId,
        public readonly ?int $productId,
        public readonly ?string $status,
        public readonly string $period,
        public readonly ?string $startDate,
        public readonly ?string $endDate,
        public readonly ?string $export,
    ) {
    }

    public static function fromArray(array $payload): self
    {
        return new self(
            keyword: trim((string) ($payload['keyword'] ?? '')),
            supplierId: self::nullableInt($payload['supplier_id'] ?? null),
            warehouseId: self::nullableInt($payload['warehouse_id'] ?? null),
            productId: self::nullableInt($payload['product_id'] ?? null),
            status: self::nullableString($payload['status'] ?? null),
            period: self::nullableString($payload['period'] ?? null) ?? 'custom',
            startDate: self::nullableString($payload['start_date'] ?? null),
            endDate: self::nullableString($payload['end_date'] ?? null),
            export: self::nullableString($payload['export'] ?? null),
        );
    }

    public function resolvedDateRange(): array
    {
        if ($this->startDate !== null || $this->endDate !== null) {
            return [$this->startDate, $this->endDate];
        }

        return match ($this->period) {
            'daily' => [now()->toDateString(), now()->toDateString()],
            'monthly' => [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()],
            'yearly' => [now()->startOfYear()->toDateString(), now()->endOfYear()->toDateString()],
            default => [null, null],
        };
    }

    public function filters(): array
    {
        [$startDate, $endDate] = $this->resolvedDateRange();

        return [
            'keyword' => $this->keyword,
            'supplier_id' => $this->supplierId,
            'warehouse_id' => $this->warehouseId,
            'product_id' => $this->productId,
            'status' => $this->status,
            'period' => $this->period,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];
    }

    public function query(?string $exportOverride = null): array
    {
        $query = [];

        if ($this->keyword !== '') {
            $query['keyword'] = $this->keyword;
        }
        if ($this->supplierId !== null) {
            $query['supplier_id'] = $this->supplierId;
        }
        if ($this->warehouseId !== null) {
            $query['warehouse_id'] = $this->warehouseId;
        }
        if ($this->productId !== null) {
            $query['product_id'] = $this->productId;
        }
        if ($this->status !== null) {
            $query['status'] = $this->status;
        }
        if ($this->period !== 'custom') {
            $query['period'] = $this->period;
        }
        if ($this->startDate !== null) {
            $query['start_date'] = $this->startDate;
        }
        if ($this->endDate !== null) {
            $query['end_date'] = $this->endDate;
        }

        $export = $exportOverride ?? $this->export;
        if ($export !== null) {
            $query['export'] = $export;
        }

        return $query;
    }

    public function exportsAsXlsx(): bool
    {
        return $this->export === 'xlsx';
    }

    private static function nullableInt(mixed $value): ?int
    {
        return $value === null || $value === '' ? null : (int) $value;
    }

    private static function nullableString(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' ? null : $value;
    }
}
