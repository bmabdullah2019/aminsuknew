<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ShippingCharge;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\ShippingZoneArea;
use App\Support\Money;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class ShippingService
{
    private ?bool $hasShippingTypeColumn = null;

    /**
     * Calculate total shipping for a shopping cart.
     *
     * Returns the shipping amount in MINOR units (paisa), or NULL if all
     * products are legacy (shipping_type IS NULL) and the caller should
     * fall through to the old flat-rate system.
     *
     * @param  Collection  $cartItems   Cart::instance('shopping')->content()
     * @param  int         $shippingChargeId  The selected shipping_charges.id (area)
     * @return int|null    Shipping in minor units, or null for full legacy fallback
     */
    public function calculateForCart(Collection $cartItems, int $shippingChargeId): ?int
    {
        if (! $this->hasShippingTypeColumn()) {
            return null; // Column not yet migrated — use legacy
        }

        $productIds = $cartItems->pluck('id')->map(fn ($id) => (int) $id)->unique()->values();
        if ($productIds->isEmpty()) {
            return null;
        }

        $products = Product::query()
            ->whereIn('id', $productIds)
            ->get(['id', 'shipping_type', 'shipping_profile_id', 'fixed_shipping_cost', 'weight', 'is_physical'])
            ->keyBy('id');

        // Check if ALL products are legacy
        $allLegacy = $products->every(fn (Product $p) => $p->shipping_type === null);
        if ($allLegacy) {
            return null; // Full legacy fallback
        }

        // Resolve the zone from the selected shipping_charges area
        $zoneId = $this->resolveZoneIdFromShippingChargeId($shippingChargeId);

        // Legacy shipping rate (for products without shipping_type)
        $legacyMinor = $this->getLegacyShippingMinor($shippingChargeId);

        $totalShippingMinor = 0;
        $hasLegacyItem = false;

        foreach ($cartItems as $item) {
            $productId = (int) $item->id;
            $qty = max(1, (int) $item->qty);
            $product = $products->get($productId);

            if (! $product) {
                continue;
            }

            $strategy = $this->resolveStrategy($product);

            switch ($strategy) {
                case 'weight_based':
                    $totalShippingMinor += $this->calculateWeightBased($product, $qty, $zoneId);
                    break;

                case 'fixed_rate':
                    $totalShippingMinor += $this->calculateFixedRate($product, $qty);
                    break;

                case 'free_shipping':
                    $totalShippingMinor += $this->calculateFreeShipping();
                    break;

                case 'digital':
                    $totalShippingMinor += $this->calculateDigital();
                    break;

                case 'legacy':
                default:
                    $hasLegacyItem = true;
                    break;
            }
        }

        // For mixed carts: add the flat-rate legacy shipping once if any legacy items exist
        if ($hasLegacyItem) {
            $totalShippingMinor += $legacyMinor;
        }

        return $totalShippingMinor;
    }

    /**
     * Calculate shipping for a single product (used by admin previews / API).
     */
    public function calculateForProduct(Product $product, int $qty, int $shippingChargeId): int
    {
        if (! $this->hasShippingTypeColumn()) {
            return $this->getLegacyShippingMinor($shippingChargeId);
        }

        $strategy = $this->resolveStrategy($product);

        return match ($strategy) {
            'weight_based' => $this->calculateWeightBased(
                $product,
                $qty,
                $this->resolveZoneIdFromShippingChargeId($shippingChargeId)
            ),
            'fixed_rate'    => $this->calculateFixedRate($product, $qty),
            'free_shipping' => $this->calculateFreeShipping(),
            'digital'       => $this->calculateDigital(),
            default         => $this->getLegacyShippingMinor($shippingChargeId),
        };
    }

    // ─── Strategy Resolver ──────────────────────────────────────────────

    /**
     * Determine which shipping strategy to use for a product.
     * If shipping_type is NULL → legacy system.
     */
    private function resolveStrategy(Product $product): string
    {
        $type = $product->shipping_type;

        if ($type === null || $type === '') {
            return 'legacy';
        }

        if (in_array($type, ['weight_based', 'fixed_rate', 'free_shipping', 'digital'], true)) {
            return $type;
        }

        // Unknown type — fall back to legacy for safety
        Log::warning('Unknown shipping_type on product', [
            'product_id' => $product->id,
            'shipping_type' => $type,
        ]);

        return 'legacy';
    }

    // ─── Strategy Implementations ───────────────────────────────────────

    /**
     * Weight-based: look up rate from shipping_rates by zone + profile + weight.
     */
    private function calculateWeightBased(Product $product, int $qty, ?int $zoneId): int
    {
        if (! $zoneId || ! $product->shipping_profile_id) {
            // Missing zone or profile — graceful fallback to zero
            Log::info('Weight-based shipping: missing zone or profile', [
                'product_id' => $product->id,
                'zone_id' => $zoneId,
                'profile_id' => $product->shipping_profile_id,
            ]);

            return 0;
        }

        $totalWeight = ((float) ($product->weight ?? 0)) * $qty;

        $rate = $this->findRate($zoneId, (int) $product->shipping_profile_id, $totalWeight);

        if (! $rate) {
            Log::info('Weight-based shipping: no matching rate found', [
                'product_id' => $product->id,
                'zone_id' => $zoneId,
                'profile_id' => $product->shipping_profile_id,
                'total_weight' => $totalWeight,
            ]);

            return 0;
        }

        $rateMinor = (int) $rate->rate_minor;
        if ($rateMinor <= 0) {
            $rateMinor = Money::fromMajor((float) $rate->rate);
        }

        return $rateMinor;
    }

    /**
     * Fixed-rate: use the product's fixed_shipping_cost × qty.
     */
    private function calculateFixedRate(Product $product, int $qty): int
    {
        $costMajor = (float) ($product->fixed_shipping_cost ?? 0);

        return Money::fromMajor($costMajor) * $qty;
    }

    private function calculateFreeShipping(): int
    {
        return 0;
    }

    private function calculateDigital(): int
    {
        return 0;
    }

    // ─── Zone & Rate Resolution ─────────────────────────────────────────

    /**
     * Resolve a ShippingZone ID from a legacy shipping_charges.id.
     * Uses the shipping_zone_areas bridge table.
     */
    public function resolveZoneIdFromShippingChargeId(int $shippingChargeId): ?int
    {
        if (! Schema::hasTable('shipping_zone_areas')) {
            return null;
        }

        return ShippingZoneArea::query()
            ->where('shipping_charge_id', $shippingChargeId)
            ->value('shipping_zone_id');
    }

    public function resolveZoneFromShippingChargeId(int $shippingChargeId): ?ShippingZone
    {
        $zoneId = $this->resolveZoneIdFromShippingChargeId($shippingChargeId);

        return $zoneId ? ShippingZone::query()->whereKey($zoneId)->first() : null;
    }

    /**
     * Find the best matching rate for zone + profile + weight.
     */
    public function findRate(int $zoneId, int $profileId, float $weight): ?ShippingRate
    {
        return ShippingRate::query()
            ->where('shipping_zone_id', $zoneId)
            ->where('shipping_profile_id', $profileId)
            ->where('min_weight', '<=', $weight)
            ->where('max_weight', '>=', $weight)
            ->where('status', 1)
            ->first();
    }

    // ─── Legacy Helpers ─────────────────────────────────────────────────

    /**
     * Get the legacy flat-rate shipping in minor units from shipping_charges table.
     */
    private function getLegacyShippingMinor(int $shippingChargeId): int
    {
        $charge = ShippingCharge::query()
            ->where('id', $shippingChargeId)
            ->where('status', 1)
            ->first(['amount', 'amount_minor']);

        if (! $charge) {
            return 0;
        }

        $minor = (int) ($charge->amount_minor ?? 0);
        if ($minor <= 0) {
            $minor = Money::fromMajor((float) $charge->amount);
        }

        return $minor;
    }

    /**
     * Check if the products table has the shipping_type column.
     * Cached for the duration of the request.
     */
    private function hasShippingTypeColumn(): bool
    {
        if ($this->hasShippingTypeColumn === null) {
            $this->hasShippingTypeColumn = Schema::hasColumn('products', 'shipping_type');
        }

        return $this->hasShippingTypeColumn;
    }
}
