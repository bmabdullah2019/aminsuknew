<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CustomerReceivableController extends Controller
{
    public function index()
    {
        $now = Carbon::now();

        // Calculate Customer Receivables from Orders and Payments
        $customers = Order::with('customer')
            ->whereNotNull('customer_id')
            ->whereNotIn('order_status', ['canceled', 'returned'])
            ->select(
                'customer_id',
                DB::raw('SUM(amount) - COALESCE(SUM((SELECT SUM(amount) FROM payments WHERE payments.order_id = orders.id AND payment_status = "paid")), 0) as total_due'),

                // 1-30 Days
                DB::raw("SUM(CASE WHEN DATEDIFF(NOW(), created_at) <= 30 THEN amount ELSE 0 END) 
                       - COALESCE((SELECT SUM(amount) FROM payments WHERE payments.order_id = orders.id AND payment_status = 'paid' AND DATEDIFF(NOW(), created_at) <= 30), 0) as due_30"),

                // 31-60 Days
                DB::raw("SUM(CASE WHEN DATEDIFF(NOW(), created_at) BETWEEN 31 AND 60 THEN amount ELSE 0 END) 
                       - COALESCE((SELECT SUM(amount) FROM payments WHERE payments.order_id = orders.id AND payment_status = 'paid' AND DATEDIFF(NOW(), created_at) BETWEEN 31 AND 60), 0) as due_60"),

                // 61-90 Days
                DB::raw("SUM(CASE WHEN DATEDIFF(NOW(), created_at) BETWEEN 61 AND 90 THEN amount ELSE 0 END) 
                       - COALESCE((SELECT SUM(amount) FROM payments WHERE payments.order_id = orders.id AND payment_status = 'paid' AND DATEDIFF(NOW(), created_at) BETWEEN 61 AND 90), 0) as due_90"),

                // 90+ Days
                DB::raw("SUM(CASE WHEN DATEDIFF(NOW(), created_at) > 90 THEN amount ELSE 0 END) 
                       - COALESCE((SELECT SUM(amount) FROM payments WHERE payments.order_id = orders.id AND payment_status = 'paid' AND DATEDIFF(NOW(), created_at) > 90), 0) as due_90_plus")
            )
            ->groupBy('customer_id')
            ->havingRaw('total_due > 0')
            ->get();

        return view('backEnd.finance.customer_receivable', compact('customers', 'now'));
    }
}
