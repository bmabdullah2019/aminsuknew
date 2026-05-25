<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CourierWebhookController extends Controller
{
    /**
     * Map courier statuses to local order_status IDs.
     */
    protected array $statusMap = [
        'pending'           => 1, // Pending
        'delivered'         => 5, // Delivered
        'partial_delivered' => 5, // Mapping partial to Delivered
        'cancelled'         => 6, // Cancelled
        'returned'          => 7, // Returned
    ];

    /**
     * Handle incoming webhook requests.
     */
    public function handle(Request $request)
    {
        // 1. Verify Authentication if a secret is configured
        $secret = env('COURIER_WEBHOOK_SECRET');
        if ($secret) {
            $token = $request->bearerToken();
            if ($token !== $secret) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized. Invalid API key.',
                ], 401);
            }
        }

        $type = $request->input('notification_type');

        if ($type === 'delivery_status') {
            return $this->handleDeliveryStatus($request);
        } elseif ($type === 'tracking_update') {
            return $this->handleTrackingUpdate($request);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Invalid notification_type.',
        ], 400);
    }

    /**
     * Handle 'delivery_status' payload.
     */
    protected function handleDeliveryStatus(Request $request)
    {
        $invoice = $request->input('invoice');
        $consignmentId = $request->input('consignment_id');
        $status = strtolower($request->input('status', ''));

        if (!$invoice) {
            return response()->json([
                'status' => 'error',
                'message' => 'Missing invoice.',
            ], 400);
        }

        $order = Order::where('invoice_id', $invoice)
            ->orWhere('steadfast_consignment_id', $consignmentId)
            ->first();

        if (!$order) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid consignment ID or invoice.',
            ], 404);
        }

        // Update steadfast specific status
        $order->steadfast_status = $status;

        // Map status to main order_status
        if (array_key_exists($status, $this->statusMap)) {
            $order->order_status = $this->statusMap[$status];
        }

        $order->save();

        Log::info('Courier Delivery Status Webhook processed successfully for Order: ' . $order->invoice_id, $request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Webhook received successfully.',
        ]);
    }

    /**
     * Handle 'tracking_update' payload.
     */
    protected function handleTrackingUpdate(Request $request)
    {
        $invoice = $request->input('invoice');
        $trackingMessage = $request->input('tracking_message');
        $updatedAt = $request->input('updated_at', now()->toDateTimeString());

        if (!$invoice) {
            return response()->json([
                'status' => 'error',
                'message' => 'Missing invoice.',
            ], 400);
        }

        $order = Order::where('invoice_id', $invoice)->first();

        if (!$order) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid consignment ID or invoice.',
            ], 404);
        }

        // Append tracking message to admin note
        $existingNote = $order->admin_note;
        $timestamp = \Carbon\Carbon::parse($updatedAt)->format('Y-m-d H:i:s');
        $newMessage = "[{$timestamp}] Tracking Update: {$trackingMessage}";

        $order->admin_note = $existingNote ? $existingNote . "\n" . $newMessage : $newMessage;
        $order->save();

        Log::info('Courier Tracking Update Webhook processed successfully for Order: ' . $order->invoice_id, $request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Webhook received successfully.',
        ]);
    }
}
