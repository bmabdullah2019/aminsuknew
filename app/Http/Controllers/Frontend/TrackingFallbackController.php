<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\PurchaseTrackingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TrackingFallbackController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected PurchaseTrackingService $trackingService
    ) {}

    /**
     * Handle the AJAX request to mark the browser-side GTM purchase event as fired.
     */
    public function fallback(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required|integer|exists:orders,id',
            'invoice_id' => 'required|string',
        ]);

        $order = Order::findOrFail($validated['order_id']);

        // Verify the invoice ID matches to ensure guest checkout and general tracking security.
        if ($order->invoice_id !== $validated['invoice_id']) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice ID mismatch.',
            ], 422);
        }

        // Lock order row and update only the browser-side GTM provider flag.
        $marked = $this->trackingService->markAsClientTracked($order);

        if ($marked) {
            return response()->json([
                'success' => true,
                'message' => 'Browser-side GTM purchase tracking recorded successfully.',
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Browser-side GTM purchase tracking already recorded.',
        ]);
    }
}
