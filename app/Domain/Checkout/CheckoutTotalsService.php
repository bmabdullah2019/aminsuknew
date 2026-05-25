<?php

namespace App\Domain\Checkout;

use App\Models\Product;
use App\Models\ShippingCharge;
use App\Services\ShippingService;
use App\Support\Money;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class CheckoutTotalsService
{
    private ?bool $hasProductMinorPriceColumn = null;

    /**
     * @param  \Illuminate\Support\Collection<int, mixed>  $cartItems
     * @return array{
     *   lines: array<int, array{row_id:string,product_id:int,qty:int,unit_minor:int,line_total_minor:int}>,
     *   subtotal_minor:int,
     *   shipping_minor:int,
     *   discount_minor:int,
     *   final_minor:int,
     *   currency:string,
     *   shipping_charge_id:int
     * }
     */
    public function calculateForShoppingCart(
        Collection $cartItems,
        int $shippingChargeId,
        int $discountMinor = 0,
        string $currency = 'BDT'
    ): array {
        if ($cartItems->isEmpty()) {
            throw ValidationException::withMessages([
                'cart' => 'Shopping cart is empty.',
            ]);
        }

        $productIds = $cartItems->pluck('id')->map(fn ($id) => (int) $id)->unique()->values();
        $products = Product::query()
            ->whereIn('id', $productIds)
            ->get($this->productSelectColumns())
            ->keyBy('id');

        $shippingCharge = ShippingCharge::query()
            ->whereKey($shippingChargeId)
            ->where('status', 1)
            ->first();

        if (! $shippingCharge) {
            throw ValidationException::withMessages([
                'area' => 'Shipping charge area is invalid.',
            ]);
        }

        $shippingMinor = (int) ($shippingCharge->amount_minor ?? 0);
        if ($shippingMinor <= 0) {
            $shippingMinor = Money::fromMajor((float) $shippingCharge->amount);
        }

        $lines = [];
        $subtotalMinor = 0;

        foreach ($cartItems as $item) {
            $productId = (int) $item->id;
            $qty = max(1, (int) $item->qty);
            $product = $products->get($productId);

            if (! $product || (int) $product->status !== 1) {
                throw ValidationException::withMessages([
                    'cart' => "Product #{$productId} is unavailable.",
                ]);
            }

            $unitMinor = $this->hasProductMinorPriceColumn()
                ? (int) ($product->new_price_minor ?? 0)
                : 0;
            if ($unitMinor <= 0) {
                $unitMinor = Money::fromMajor((float) $product->new_price);
            }

            $lineMinor = $unitMinor * $qty;
            $subtotalMinor += $lineMinor;

            $lines[] = [
                'row_id' => (string) $item->rowId,
                'product_id' => $productId,
                'qty' => $qty,
                'unit_minor' => $unitMinor,
                'line_total_minor' => $lineMinor,
            ];
        }

        $newEngineShipping = app(ShippingService::class)->calculateForCart($cartItems, $shippingChargeId);
        if ($newEngineShipping !== null) {
            $shippingMinor = $newEngineShipping;
        }

        $discountMinor = Money::clampNonNegative((int) $discountMinor);
        $maxDiscount = $subtotalMinor + $shippingMinor;
        if ($discountMinor > $maxDiscount) {
            $discountMinor = $maxDiscount;
        }

        $finalMinor = Money::clampNonNegative($subtotalMinor + $shippingMinor - $discountMinor);

        return [
            'lines' => $lines,
            'subtotal_minor' => $subtotalMinor,
            'shipping_minor' => $shippingMinor,
            'discount_minor' => $discountMinor,
            'final_minor' => $finalMinor,
            'currency' => $currency,
            'shipping_charge_id' => (int) $shippingCharge->id,
        ];
    }

    /**
     * @return array<int, string>
     */
    private function productSelectColumns(): array
    {
        $columns = ['id', 'status', 'new_price', 'currency'];
        if ($this->hasProductMinorPriceColumn()) {
            $columns[] = 'new_price_minor';
        }

        return $columns;
    }

    private function hasProductMinorPriceColumn(): bool
    {
        if ($this->hasProductMinorPriceColumn === null) {
            $this->hasProductMinorPriceColumn = Schema::hasColumn('products', 'new_price_minor');
        }

        return $this->hasProductMinorPriceColumn;
    }
}
