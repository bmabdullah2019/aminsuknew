<?php

namespace App\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardIntegrityService
{
    /**
     * @var array<int,string>
     */
    private array $scopedTables = [
        'warehouses',
        'purchase_orders',
        'purchase_items',
        'orders',
        'order_details',
        'expenses',
        'payments',
        'stocks',
        'warehouse_stock',
        'inventories',
        'stock_movements',
        'supplier_payments',
        'supplier_ledgers',
        'ledger_journals',
        'payment_events',
        'order_state_transitions',
        'users',
        'cash_accounts',
        'bank_accounts',
    ];

    /**
     * @return array<string,mixed>
     */
    public function summary(int $ttlSeconds = 300): array
    {
        return Cache::remember('dashboard.integrity.summary', $ttlSeconds, function (): array {
            return $this->computeSummary();
        });
    }

    /**
     * @return array<string,mixed>
     */
    private function computeSummary(): array
    {
        $checkedAt = now()->toIso8601String();

        $summary = [
            'ok' => true,
            'available' => true,
            'issues_total' => 0,
            'branches_total' => 0,
            'main_branch_exists' => false,
            'scoped_tables_checked' => count($this->scopedTables),
            'scoped_tables_present' => 0,
            'missing_branch_id_columns' => 0,
            'null_or_zero_branch_ids' => 0,
            'orphan_branch_ids' => 0,
            'payments_without_context' => 0,
            'payments_with_double_context' => 0,
            'journal_entries_unbalanced' => 0,
            'inventory_warehouse_stock_mismatch' => 0,
            'checked_at' => $checkedAt,
            'message' => '',
        ];

        if (! Schema::hasTable('branches')) {
            $summary['ok'] = false;
            $summary['available'] = false;
            $summary['issues_total'] = 1;
            $summary['message'] = 'branches table not found';

            return $summary;
        }

        $summary['branches_total'] = (int) DB::table('branches')->count();
        $summary['main_branch_exists'] = DB::table('branches')->where('code', 'MAIN')->exists();
        if (! $summary['main_branch_exists']) {
            $summary['issues_total']++;
        }

        foreach ($this->scopedTables as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $summary['scoped_tables_present']++;

            if (! Schema::hasColumn($table, 'branch_id')) {
                $summary['missing_branch_id_columns']++;

                continue;
            }

            $summary['null_or_zero_branch_ids'] += (int) DB::table($table)
                ->where(function (Builder $query): void {
                    $query->whereNull('branch_id')
                        ->orWhere('branch_id', 0);
                })
                ->count();

            $summary['orphan_branch_ids'] += (int) DB::table($table)
                ->leftJoin('branches', 'branches.id', '=', $table.'.branch_id')
                ->whereNotNull($table.'.branch_id')
                ->whereNull('branches.id')
                ->count();
        }

        if (Schema::hasTable('payments')) {
            $hasOrderId = Schema::hasColumn('payments', 'order_id');
            $hasPurchaseOrderId = Schema::hasColumn('payments', 'purchase_order_id');

            if ($hasOrderId && $hasPurchaseOrderId) {
                $summary['payments_without_context'] = (int) DB::table('payments')
                    ->whereNull('order_id')
                    ->whereNull('purchase_order_id')
                    ->count();
                $summary['payments_with_double_context'] = (int) DB::table('payments')
                    ->whereNotNull('order_id')
                    ->whereNotNull('purchase_order_id')
                    ->count();
            } else {
                $summary['issues_total']++;
                $summary['message'] = 'payments context columns missing';
            }
        }

        if (Schema::hasTable('journal_entries') && Schema::hasTable('journal_entry_items')) {
            $summary['journal_entries_unbalanced'] = (int) DB::table('journal_entry_items')
                ->selectRaw('journal_entry_id')
                ->groupBy('journal_entry_id')
                ->havingRaw('ABS(ROUND(COALESCE(SUM(debit), 0), 2) - ROUND(COALESCE(SUM(credit), 0), 2)) > 0.01')
                ->count();
        }

        if (Schema::hasTable('inventories') && Schema::hasTable('product_variants') && Schema::hasTable('warehouse_stock')) {
            $summary['inventory_warehouse_stock_mismatch'] = (int) DB::table(DB::raw('(
                SELECT
                    inv.warehouse_id AS warehouse_id,
                    pv.product_id AS product_id,
                    ROUND(COALESCE(SUM(inv.quantity_available), 0), 2) AS inv_physical,
                    ROUND(COALESCE(SUM(inv.quantity_reserved), 0), 2) AS inv_reserved,
                    ROUND(COALESCE(ws.physical_quantity, 0), 2) AS ws_physical,
                    ROUND(COALESCE(ws.reserved_quantity, 0), 2) AS ws_reserved
                FROM inventories inv
                INNER JOIN product_variants pv ON pv.id = inv.product_variant_id
                LEFT JOIN warehouse_stock ws
                    ON ws.warehouse_id = inv.warehouse_id
                    AND ws.product_id = pv.product_id
                GROUP BY inv.warehouse_id, pv.product_id, ws.physical_quantity, ws.reserved_quantity
            ) AS stock_cmp'))
                ->whereRaw('ABS(inv_physical - ws_physical) > 0.01 OR ABS(inv_reserved - ws_reserved) > 0.01')
                ->count();
        }

        $summary['issues_total'] +=
            (int) $summary['missing_branch_id_columns'] +
            (int) $summary['null_or_zero_branch_ids'] +
            (int) $summary['orphan_branch_ids'] +
            (int) $summary['payments_without_context'] +
            (int) $summary['payments_with_double_context'] +
            (int) $summary['journal_entries_unbalanced'] +
            (int) $summary['inventory_warehouse_stock_mismatch'];

        $summary['ok'] = $summary['issues_total'] === 0;
        if ($summary['message'] === '' && ! $summary['ok']) {
            $summary['message'] = 'Integrity issues detected';
        }

        return $summary;
    }
}
