<?php

namespace App\Support;

final class SupplierReportsQueryData
{
    private const DEFAULT_TYPE = 'aging';

    private const ALLOWED_TYPES = [
        'aging',
        'performance',
        'dues',
    ];

    private const ALLOWED_EXPORTS = [
        'csv',
        'xlsx',
    ];

    public function __construct(
        public readonly string $type,
        public readonly ?string $export,
        public readonly bool $hasInvalidType = false,
        public readonly bool $hasInvalidExport = false,
    ) {
    }

    public static function fromArray(array $payload): self
    {
        $rawType = self::normalize($payload['type'] ?? null);
        $rawExport = self::normalize($payload['export'] ?? null);

        $hasInvalidType = $rawType !== '' && ! in_array($rawType, self::ALLOWED_TYPES, true);
        $hasInvalidExport = $rawExport !== '' && ! in_array($rawExport, self::ALLOWED_EXPORTS, true);

        return new self(
            type: $hasInvalidType || $rawType === '' ? self::DEFAULT_TYPE : $rawType,
            export: $hasInvalidExport || $rawExport === '' ? null : $rawExport,
            hasInvalidType: $hasInvalidType,
            hasInvalidExport: $hasInvalidExport,
        );
    }

    public function shouldRedirectToCanonicalRoute(): bool
    {
        return $this->hasInvalidType;
    }

    public function canonicalQuery(): array
    {
        return ['type' => $this->type];
    }

    public function shouldExport(): bool
    {
        return $this->export !== null;
    }

    public function exportsAsXlsx(): bool
    {
        return $this->export === 'xlsx';
    }

    private static function normalize(mixed $value): string
    {
        return strtolower(trim((string) ($value ?? '')));
    }
}
