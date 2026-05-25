<?php

namespace App\Modules\Reports\Services;

use App\Models\ReturnOrder;
use App\Modules\Reports\ReportFilter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ReturnsAnalyticsService
{
    /**
     * Get returns analytics.
     */
    public function getReturnsReport(ReportFilter $filter, int $perPage = 15): LengthAwarePaginator
    {
        $query = ReturnOrder::query()
            ->with(['order', 'customer', 'returnReason', 'creator'])
            ->whereBetween('created_at', [
                $filter->startDate.' 00:00:00',
                $filter->endDate.' 23:59:59',
            ])
            ->orderBy('created_at', 'desc');

        if ($filter->customerId) {
            $query->where('customer_id', $filter->customerId);
        }

        if ($filter->status) {
            $query->where('return_status', $filter->status);
        }

        return $query->paginate($perPage);
    }

    /**
     * Get return statistics.
     */
    public function getReturnStats(ReportFilter $filter): array
    {
        $stats = ReturnOrder::query()
            ->whereBetween('created_at', [
                $filter->startDate.' 00:00:00',
                $filter->endDate.' 23:59:59',
            ])
            ->selectRaw('COUNT(*) as total_returns')
            ->selectRaw('SUM(total_return_value) as total_value')
            ->selectRaw('SUM(refund_amount) as total_refunded')
            ->first();

        $topReasons = DB::table('return_orders')
            ->join('return_reasons', 'return_reasons.id', '=', 'return_orders.return_reason_id')
            ->whereBetween('return_orders.created_at', [
                $filter->startDate.' 00:00:00',
                $filter->endDate.' 23:59:59',
            ])
            ->groupBy('return_reasons.reason_name')
            ->selectRaw('return_reasons.reason_name, COUNT(*) as count')
            ->orderBy('count', 'desc')
            ->limit(5)
            ->get();

        return [
            'total_returns' => (int) $stats->total_returns,
            'total_value' => (float) $stats->total_value,
            'total_refunded' => (float) $stats->total_refunded,
            'top_reasons' => $topReasons,
        ];
    }
}
