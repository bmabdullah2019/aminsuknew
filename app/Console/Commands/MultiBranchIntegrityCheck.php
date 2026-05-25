<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MultiBranchIntegrityCheck extends Command
{
    /**
     * @var string
     */
    protected $signature = 'multibranch:integrity-check
        {--json : Output machine-readable JSON summary}
        {--max_issues=200 : Maximum issue rows to include in output}';

    /**
     * @var string
     */
    protected $description = 'Validate multi-branch scoping, branch FKs, payment context, journal balance, and stock aggregate integrity';

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

    public function handle(): int
    {
        $json = (bool) $this->option('json');
        $maxIssues = (int) ($this->option('max_issues') ?? 200);
        if ($maxIssues <= 0) {
            return $this->emitError('Invalid --max_issues value. Use a positive integer.', self::INVALID, $json);
        }

        $issues = [];
        $metrics = [];

        if (! Schema::hasTable('branches')) {
            $issues[] = [
                'category' => 'schema',
                'table' => 'branches',
                'issue' => 'missing_table',
                'count' => 1,
            ];

            $payload = [
                'ok' => false,
                'metrics' => [
                    'scoped_tables_checked' => 0,
                    'scoped_tables_present' => 0,
                ],
                'issues_total' => 1,
                'issues_sample' => array_slice($issues, 0, $maxIssues),
            ];

            if ($json) {
                $this->line((string) json_encode($payload));

                return self::SUCCESS;
            }

            $this->error('branches table does not exist. Run branch migration first.');
            $this->table(['category', 'table', 'issue', 'count'], $payload['issues_sample']);

            return self::SUCCESS;
        }

        $metrics['branches_total'] = (int) DB::table('branches')->count();
        $metrics['main_branch_exists'] = DB::table('branches')->where('code', 'MAIN')->exists() ? 1 : 0;
        if ($metrics['main_branch_exists'] !== 1) {
            $issues[] = [
                'category' => 'branch_seed',
                'table' => 'branches',
                'issue' => 'main_branch_missing',
                'count' => 1,
            ];
        }

        $scopedPresent = 0;
        foreach ($this->scopedTables as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $scopedPresent++;

            if (! Schema::hasColumn($table, 'branch_id')) {
                $issues[] = [
                    'category' => 'schema',
                    'table' => $table,
                    'issue' => 'missing_branch_id_column',
                    'count' => 1,
                ];

                continue;
            }

            $nullOrZero = (int) DB::table($table)
                ->where(function (Builder $query): void {
                    $query->whereNull('branch_id')
                        ->orWhere('branch_id', 0);
                })
                ->count();

            if ($nullOrZero > 0) {
                $issues[] = [
                    'category' => 'branch_scope',
                    'table' => $table,
                    'issue' => 'null_or_zero_branch_id',
                    'count' => $nullOrZero,
                ];
            }

            $orphans = (int) DB::table($table)
                ->leftJoin('branches', 'branches.id', '=', $table.'.branch_id')
                ->whereNotNull($table.'.branch_id')
                ->whereNull('branches.id')
                ->count();

            if ($orphans > 0) {
                $issues[] = [
                    'category' => 'branch_fk',
                    'table' => $table,
                    'issue' => 'orphan_branch_id',
                    'count' => $orphans,
                ];
            }
        }

        $metrics['scoped_tables_checked'] = count($this->scopedTables);
        $metrics['scoped_tables_present'] = $scopedPresent;

        $this->checkPaymentContext($issues, $metrics);
        $this->checkJournalBalance($issues, $metrics);
        $this->checkStockAggregateConsistency($issues, $metrics);

        $issuesTotal = array_sum(array_map(static fn (array $row): int => (int) ($row['count'] ?? 0), $issues));
        $ok = $issuesTotal === 0;

        $payload = [
            'ok' => $ok,
            'generated_at' => now()->toIso8601String(),
            'metrics' => $metrics,
            'issues_total' => $issuesTotal,
            'issues_sample' => array_slice($issues, 0, $maxIssues),
        ];

        if ($json) {
            $this->line((string) json_encode($payload));

            return self::SUCCESS;
        }

        $this->table(
            ['metric', 'value'],
            array_map(static fn (string $key, mixed $value): array => [
                'metric' => $key,
                'value' => is_scalar($value) ? (string) $value : json_encode($value),
            ], array_keys($metrics), array_values($metrics))
        );

        if (empty($issues)) {
            $this->info('No multi-branch integrity issues detected.');

            return self::SUCCESS;
        }

        $this->warn('Multi-branch integrity issues detected.');
        $this->table(['category', 'table', 'issue', 'count'], $payload['issues_sample']);

        return self::SUCCESS;
    }

    /**
     * @param  array<int,array<string,mixed>>  $issues
     * @param  array<string,mixed>  $metrics
     */
    private function checkPaymentContext(array &$issues, array &$metrics): void
    {
        if (! Schema::hasTable('payments')) {
            return;
        }

        $hasPurchaseOrderId = Schema::hasColumn('payments', 'purchase_order_id');
        $hasOrderId = Schema::hasColumn('payments', 'order_id');

        if (! $hasOrderId) {
            $issues[] = [
                'category' => 'payment_context',
                'table' => 'payments',
                'issue' => 'missing_order_id_column',
                'count' => 1,
            ];

            return;
        }

        if (! $hasPurchaseOrderId) {
            $issues[] = [
                'category' => 'payment_context',
                'table' => 'payments',
                'issue' => 'missing_purchase_order_id_column',
                'count' => 1,
            ];

            return;
        }

        $bothNull = (int) DB::table('payments')
            ->whereNull('order_id')
            ->whereNull('purchase_order_id')
            ->count();
        $bothSet = (int) DB::table('payments')
            ->whereNotNull('order_id')
            ->whereNotNull('purchase_order_id')
            ->count();

        $metrics['payments_without_context'] = $bothNull;
        $metrics['payments_with_double_context'] = $bothSet;

        if ($bothNull > 0) {
            $issues[] = [
                'category' => 'payment_context',
                'table' => 'payments',
                'issue' => 'missing_order_and_purchase_context',
                'count' => $bothNull,
            ];
        }

        if ($bothSet > 0) {
            $issues[] = [
                'category' => 'payment_context',
                'table' => 'payments',
                'issue' => 'both_order_and_purchase_set',
                'count' => $bothSet,
            ];
        }

        if (Schema::hasTable('orders') && Schema::hasColumn('orders', 'branch_id')) {
            $orderBranchMismatch = (int) DB::table('payments')
                ->join('orders', 'orders.id', '=', 'payments.order_id')
                ->whereNotNull('payments.order_id')
                ->whereRaw('COALESCE(payments.branch_id, 0) <> COALESCE(orders.branch_id, 0)')
                ->count();

            $metrics['payment_order_branch_mismatch'] = $orderBranchMismatch;
            if ($orderBranchMismatch > 0) {
                $issues[] = [
                    'category' => 'payment_branch',
                    'table' => 'payments',
                    'issue' => 'branch_mismatch_with_orders',
                    'count' => $orderBranchMismatch,
                ];
            }
        }

        if (Schema::hasTable('purchase_orders') && Schema::hasColumn('purchase_orders', 'branch_id')) {
            $purchaseBranchMismatch = (int) DB::table('payments')
                ->join('purchase_orders', 'purchase_orders.id', '=', 'payments.purchase_order_id')
                ->whereNotNull('payments.purchase_order_id')
                ->whereRaw('COALESCE(payments.branch_id, 0) <> COALESCE(purchase_orders.branch_id, 0)')
                ->count();

            $metrics['payment_purchase_branch_mismatch'] = $purchaseBranchMismatch;
            if ($purchaseBranchMismatch > 0) {
                $issues[] = [
                    'category' => 'payment_branch',
                    'table' => 'payments',
                    'issue' => 'branch_mismatch_with_purchase_orders',
                    'count' => $purchaseBranchMismatch,
                ];
            }
        }
    }

    /**
     * @param  array<int,array<string,mixed>>  $issues
     * @param  array<string,mixed>  $metrics
     */
    private function checkJournalBalance(array &$issues, array &$metrics): void
    {
        if (! Schema::hasTable('journal_entries') || ! Schema::hasTable('journal_entry_items')) {
            return;
        }

        $journalWithoutItems = (int) DB::table('journal_entries')
            ->leftJoin('journal_entry_items', 'journal_entry_items.journal_entry_id', '=', 'journal_entries.id')
            ->whereNull('journal_entry_items.id')
            ->count();

        $unbalanced = DB::table('journal_entry_items')
            ->selectRaw('journal_entry_id, ROUND(COALESCE(SUM(debit), 0), 2) AS debit_total, ROUND(COALESCE(SUM(credit), 0), 2) AS credit_total')
            ->groupBy('journal_entry_id')
            ->havingRaw('ABS(ROUND(COALESCE(SUM(debit), 0), 2) - ROUND(COALESCE(SUM(credit), 0), 2)) > 0.01')
            ->count();

        $metrics['journal_entries_without_items'] = $journalWithoutItems;
        $metrics['journal_entries_unbalanced'] = (int) $unbalanced;

        if ($journalWithoutItems > 0) {
            $issues[] = [
                'category' => 'journal',
                'table' => 'journal_entries',
                'issue' => 'missing_journal_items',
                'count' => $journalWithoutItems,
            ];
        }

        if ($unbalanced > 0) {
            $issues[] = [
                'category' => 'journal',
                'table' => 'journal_entry_items',
                'issue' => 'unbalanced_entries',
                'count' => (int) $unbalanced,
            ];
        }
    }

    /**
     * @param  array<int,array<string,mixed>>  $issues
     * @param  array<string,mixed>  $metrics
     */
    private function checkStockAggregateConsistency(array &$issues, array &$metrics): void
    {
        if (! Schema::hasTable('inventories') || ! Schema::hasTable('product_variants') || ! Schema::hasTable('warehouse_stock')) {
            return;
        }

        $inventoryVsWarehouse = (int) DB::table(DB::raw('(
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

        $metrics['inventory_warehouse_stock_mismatch'] = $inventoryVsWarehouse;
        if ($inventoryVsWarehouse > 0) {
            $issues[] = [
                'category' => 'stock_consistency',
                'table' => 'inventories_vs_warehouse_stock',
                'issue' => 'aggregate_mismatch',
                'count' => $inventoryVsWarehouse,
            ];
        }
    }

    private function emitError(string $message, int $code, bool $json): int
    {
        if ($json) {
            $this->line((string) json_encode([
                'ok' => false,
                'error' => $message,
            ]));
        } else {
            $this->error($message);
        }

        return $code;
    }
}
