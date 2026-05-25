<?php

namespace App\Http\Controllers\Api;

use App\Domain\Checkout\CheckoutTotalsService;
use App\Http\Controllers\Controller;
use App\Support\Money;
use Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class ShippingCalculationController extends Controller
{
    public function calculate(Request $request)
    {
        $validated = $request->validate([
            'area' => 'required|integer|exists:shipping_charges,id',
        ]);

        $totals = app(CheckoutTotalsService::class)->calculateForShoppingCart(
            Cart::instance('shopping')->content(),
            (int) $validated['area'],
            Money::fromMajor((float) Session::get('discount', 0))
        );

        Session::put('shipping', Money::toMajorFloat((int) $totals['shipping_minor']));

        return response()->json([
            'success' => true,
            'shipping_minor' => (int) $totals['shipping_minor'],
            'subtotal_minor' => (int) $totals['subtotal_minor'],
            'discount_minor' => (int) $totals['discount_minor'],
            'final_minor' => (int) $totals['final_minor'],
            'currency' => $totals['currency'],
            'shipping_charge_id' => (int) $totals['shipping_charge_id'],
        ]);
    }
}
