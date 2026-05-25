<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Exceptions;

use RuntimeException;

final class StockOperationException extends RuntimeException
{
    public static function invalidQuantity(string $operation, float $quantity): self
    {
        return new self(sprintf(
            'Stock operation [%s] requires quantity greater than zero. Given: %s',
            $operation,
            (string) $quantity
        ));
    }

    public static function missingWarehouse(int $orderId, int $productId): self
    {
        return new self(sprintf(
            'Warehouse is required for stock mutation. Order #%d, product #%d.',
            $orderId,
            $productId
        ));
    }

    public static function unsupportedVariantOperation(
        string $operation,
        ?int $referenceId,
        string $referenceType
    ): self {
        return new self(sprintf(
            'Variant stock operation [%s] requires order reference. Reference type [%s], reference id [%s].',
            $operation,
            $referenceType,
            $referenceId === null ? 'null' : (string) $referenceId
        ));
    }

    public static function operationFailed(string $operation, string $message): self
    {
        return new self(sprintf('Stock operation [%s] failed: %s', $operation, $message));
    }
}
