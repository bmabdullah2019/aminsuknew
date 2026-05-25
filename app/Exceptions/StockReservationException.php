<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

class StockReservationException extends RuntimeException
{
    public const REASON_STOCK_NOT_FOUND = 'stock_not_found';

    public const REASON_INSUFFICIENT_STOCK = 'insufficient_stock';

    /** @var string */
    protected $reason;

    /** @var int */
    protected $warehouseId;

    /** @var int */
    protected $productId;

    /** @var int|null */
    protected $productVariantId;

    /** @var float */
    protected $requestedQuantity;

    /** @var float|null */
    protected $availableQuantity;


    public function __construct(
        string $reason,
        int $warehouseId,
        int $productId,
        ?int $productVariantId,
        float $requestedQuantity,
        ?float $availableQuantity = null,
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null
    ) {

        $this->reason = $reason;
        $this->warehouseId = $warehouseId;
        $this->productId = $productId;
        $this->productVariantId = $productVariantId;
        $this->requestedQuantity = $requestedQuantity;

        $this->availableQuantity = $availableQuantity;

        parent::__construct($message, $code, $previous);
    }

    public static function stockNotFound(int $warehouseId, int $productId, float $requestedQuantity, ?int $productVariantId = null): self
    {
        return new self(
            self::REASON_STOCK_NOT_FOUND,
            $warehouseId,
            $productId,
            $productVariantId,
            $requestedQuantity,
            null,
            "Stock not found for product {$productId} in warehouse {$warehouseId}"
        );
    }

    public static function insufficientStock(int $warehouseId, int $productId, float $requestedQuantity, float $availableQuantity, ?int $productVariantId = null): self
    {
        return new self(
            self::REASON_INSUFFICIENT_STOCK,
            $warehouseId,
            $productId,
            $productVariantId,
            $requestedQuantity,
            $availableQuantity,
            "Insufficient stock. Available: {$availableQuantity}, Required: {$requestedQuantity}"
        );
    }


    public function getReason(): string
    {
        return $this->reason;
    }

    public function getWarehouseId(): int
    {
        return $this->warehouseId;
    }

    public function getProductId(): int
    {
        return $this->productId;
    }

    public function getProductVariantId(): ?int
    {
        return $this->productVariantId;
    }


    public function getRequestedQuantity(): float
    {
        return $this->requestedQuantity;
    }

    public function getAvailableQuantity(): ?float
    {
        return $this->availableQuantity;
    }
}
