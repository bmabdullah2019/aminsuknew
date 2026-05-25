<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SupplierAgingController extends Controller
{
    public function index()
    {
        $now = Carbon::now();

        // Compute Payables directly from Purchase Orders that are outstanding
        $suppliers = PurchaseOrder::with('supplier')
            ->whereNotNull('supplier_id')
            ->where('status', '!=', 'cancelled')
            ->select(
                'supplier_id',
                DB::raw('SUM(total_cost) - COALESCE(SUM((SELECT SUM(amount) FROM supplier_payments WHERE supplier_payments.supplier_id = purchase_orders.supplier_id)), 0) as total_due'),

                // 1-30 Days
                DB::raw('SUM(CASE WHEN DATEDIFF(NOW(), created_at) <= 30 THEN total_cost ELSE 0 END) 
                       - COALESCE((SELECT SUM(amount) FROM supplier_payments WHERE supplier_payments.supplier_id = purchase_orders.supplier_id AND DATEDIFF(NOW(), payment_date) <= 30), 0) as due_30'),

                // 31-60 Days
                DB::raw('SUM(CASE WHEN DATEDIFF(NOW(), created_at) BETWEEN 31 AND 60 THEN total_cost ELSE 0 END) 
                       - COALESCE((SELECT SUM(amount) FROM supplier_payments WHERE supplier_payments.supplier_id = purchase_orders.supplier_id AND DATEDIFF(NOW(), payment_date) BETWEEN 31 AND 60), 0) as due_60'),

                // 61-90 Days
                DB::raw('SUM(CASE WHEN DATEDIFF(NOW(), created_at) BETWEEN 61 AND 90 THEN total_cost ELSE 0 END) 
                       - COALESCE((SELECT SUM(amount) FROM supplier_payments WHERE supplier_payments.supplier_id = purchase_orders.supplier_id AND DATEDIFF(NOW(), payment_date) BETWEEN 61 AND 90), 0) as due_90'),

                // 90+ Days
                DB::raw('SUM(CASE WHEN DATEDIFF(NOW(), created_at) > 90 THEN total_cost ELSE 0 END) 
                       - COALESCE((SELECT SUM(amount) FROM supplier_payments WHERE supplier_payments.supplier_id = purchase_orders.supplier_id AND DATEDIFF(NOW(), payment_date) > 90), 0) as due_90_plus')
            )
            ->groupBy('supplier_id')
            ->havingRaw('total_due > 0')
            ->get();

        return view('backEnd.finance.supplier_aging', compact('suppliers', 'now'));
    }
}
