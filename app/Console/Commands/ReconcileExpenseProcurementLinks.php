<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ReconcileExpenseProcurementLinks extends Command
{
    /**
     * @var string
     */
    protected $signature = 'expenses:reconcile-procurement-links
        {--expense_id= : Specific expense ID}
        {--supplier_id= : Filter by supplier ID}
        {--date_from= : Filter expenses on/after this date (YYYY-MM-DD)}
        {--date_to= : Filter expenses on/before this date (YYYY-MM-DD)}
        {--status= : Filter by expense status (pending,approved,rejected,paid)}
        {--apply : Apply safe supplier auto-link fixes from PO/GRN}
        {--strict : Exit with failure code when unresolved issues remain}
        {--json : Output machine-readable JSON summary}
        {--export= : Write unresolved issues to CSV (relative paths resolve under storage/app/reconciliation)}
        {--max_issues=200 : Maximum unresolved rows to print}';

    /**
     * @var string
     */
    protected $description = 'Reconcile expense procurement links (supplier, purchase order, GRN) for integrity and consistency';

    public function handle(): int
    {
        $json = (bool) $this->option('json');

        if (! Schema::hasTable('expenses')) {
            return $this->emitError('Expenses table not found.', self::FAILURE, $json);
        }

        $hasSupplierColumn = Schema::hasColumn('expenses', 'supplier_id');
        $hasPurchaseOrderColumn = Schema::hasColumn('expenses', 'purchase_order_id');
        $hasGrnColumn = Schema::hasColumn('expenses', 'grn_id');
        $hasExpenseDateColumn = Schema::hasColumn('expenses', 'expense_date');
        $hasStatusColumn = Schema::hasColumn('expenses', 'status');

        if (! $hasSupplierColumn && ! $hasPurchaseOrderColumn && ! $hasGrnColumn) {
            if ($json) {
                $this->line((string) json_encode([
                    'ok' => true,
                    'message' => 'No procurement link columns found on expenses table. Nothing to reconcile.',
                    'metrics' => [
                        'checked' => 0,
                        'balanced' => 0,
                        'missing_purchase_order' => 0,
                        'missing_grn' => 0,
                        'missing_supplier' => 0,
                        'supplier_mismatch' => 0,
                        'po_grn_supplier_conflict' => 0,
                        'supplier_autofixable' => 0,
                        'supplier_fixed' => 0,
                        'unresolved_issues' => 0,
                    ],
                    'issues_total' => 0,
                    'issues_sample' => [],
                ]));
            } else {
                $this->warn('No procurement link columns found on expenses table. Nothing to reconcile.');
            }

            return self::SUCCESS;
        }

        $hasSuppliersTable = Schema::hasTable('suppliers');
        $hasPurchaseOrdersTable = Schema::hasTable('purchase_orders');
        $hasGrnsTable = Schema::hasTable('grns');

        $apply = (bool) $this->option('apply');
        $strict = (bool) $this->option('strict');
        $maxIssues = (int) ($this->option('max_issues') ?? 200);
        if ($maxIssues <= 0) {
            return $this->emitError('Invalid --max_issues value. Use a positive integer.', self::INVALID, $json);
        }

        try {
            $query = $this->buildQuery(
                $hasSupplierColumn,
                $hasPurchaseOrderColumn,
                $hasGrnColumn,
                $hasExpenseDateColumn,
                $hasStatusColumn
            );
        } catch (\InvalidArgumentException $e) {
            return $this->emitError($e->getMessage(), self::INVALID, $json);
        }

        $totalCandidates = (clone $query)->count();
        if (! $json) {
            $this->info('Reconciling expense procurement links...');
            $this->line('mode: '.($apply ? 'apply' : 'dry-run'));
            $this->line('strict: '.($strict ? 'yes' : 'no'));
            $this->line('candidates: '.$totalCandidates);
        }

        $metrics = [
            'checked' => 0,
            'balanced' => 0,
            'missing_purchase_order' => 0,
            'missing_grn' => 0,
            'missing_supplier' => 0,
            'supplier_mismatch' => 0,
            'po_grn_supplier_conflict' => 0,
            'supplier_autofixable' => 0,
            'supplier_fixed' => 0,
        ];
        $issues = [];

        $query->chunkById(200, function ($expenseRows) use (
            $apply,
            $hasSupplierColumn,
            $hasPurchaseOrderColumn,
            $hasGrnColumn,
            $hasSuppliersTable,
            $hasPurchaseOrdersTable,
            $hasGrnsTable,
            &$metrics,
            &$issues
        ): void {
            $purchaseOrderMap = [];
            $grnMap = [];
            $supplierExistsMap = [];

            if ($hasPurchaseOrderColumn && $hasPurchaseOrdersTable) {
                $purchaseOrderIds = $expenseRows
                    ->pluck('purchase_order_id')
                    ->filter(fn ($id) => (int) $id > 0)
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->values()
                    ->all();

                if (! empty($purchaseOrderIds)) {
                    $purchaseOrderMap = DB::table('purchase_orders')
                        ->whereIn('id', $purchaseOrderIds)
                        ->pluck('supplier_id', 'id')
                        ->map(fn ($supplierId) => (int) ($supplierId ?? 0))
                        ->all();
                }
            }

            if ($hasGrnColumn && $hasGrnsTable) {
                $grnIds = $expenseRows
                    ->pluck('grn_id')
                    ->filter(fn ($id) => (int) $id > 0)
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->values()
                    ->all();

                if (! empty($grnIds)) {
                    $grnMap = DB::table('grns')
                        ->whereIn('id', $grnIds)
                        ->pluck('supplier_id', 'id')
                        ->map(fn ($supplierId) => (int) ($supplierId ?? 0))
                        ->all();
                }
            }

            if ($hasSupplierColumn && $hasSuppliersTable) {
                $supplierIds = $expenseRows
                    ->pluck('supplier_id')
                    ->filter(fn ($id) => (int) $id > 0)
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->values()
                    ->all();

                if (! empty($supplierIds)) {
                    $supplierExistsMap = DB::table('suppliers')
                        ->whereIn('id', $supplierIds)
                        ->pluck('id', 'id')
                        ->map(fn ($id) => (int) $id)
                        ->all();
                }
            }

            foreach ($expenseRows as $expense) {
                $metrics['checked']++;

                $expenseId = (int) $expense->id;
                $expenseNumber = (string) ($expense->expense_number ?? ('EXP-'.$expenseId));
                $supplierId = $hasSupplierColumn ? (int) ($expense->supplier_id ?? 0) : 0;
                $purchaseOrderId = $hasPurchaseOrderColumn ? (int) ($expense->purchase_order_id ?? 0) : 0;
                $grnId = $hasGrnColumn ? (int) ($expense->grn_id ?? 0) : 0;

                $issueCodes = [];

                $purchaseOrderSupplierId = 0;
                if ($purchaseOrderId > 0) {
                    if (! array_key_exists($purchaseOrderId, $purchaseOrderMap)) {
                        $issueCodes[] = 'missing_purchase_order';
                        $metrics['missing_purchase_order']++;
                    } else {
                        $purchaseOrderSupplierId = (int) $purchaseOrderMap[$purchaseOrderId];
                    }
                }

                $grnSupplierId = 0;
                if ($grnId > 0) {
                    if (! array_key_exists($grnId, $grnMap)) {
                        $issueCodes[] = 'missing_grn';
                        $metrics['missing_grn']++;
                    } else {
                        $grnSupplierId = (int) $grnMap[$grnId];
                    }
                }

                if ($supplierId > 0 && $hasSuppliersTable && ! array_key_exists($supplierId, $supplierExistsMap)) {
                    $issueCodes[] = 'missing_supplier';
                    $metrics['missing_supplier']++;
                }

                $referenceSupplierId = 0;
                if ($purchaseOrderSupplierId > 0 && $grnSupplierId > 0 && $purchaseOrderSupplierId !== $grnSupplierId) {
                    $issueCodes[] = 'po_grn_supplier_conflict';
                    $metrics['po_grn_supplier_conflict']++;
                } else {
                    $referenceSupplierId = max($purchaseOrderSupplierId, $grnSupplierId);
                }

                if ($supplierId > 0 && $referenceSupplierId > 0 && $supplierId !== $referenceSupplierId) {
                    $issueCodes[] = 'supplier_mismatch';
                    $metrics['supplier_mismatch']++;
                }

                $fixableMissingSupplier = $hasSupplierColumn
                    && $supplierId <= 0
                    && $referenceSupplierId > 0;

                if ($fixableMissingSupplier) {
                    $metrics['supplier_autofixable']++;
                    $issueCodes[] = 'supplier_missing_from_reference';
                }

                if ($apply && $fixableMissingSupplier) {
                    DB::table('expenses')
                        ->where('id', $expenseId)
                        ->update([
                            'supplier_id' => $referenceSupplierId,
                            'updated_at' => now(),
                        ]);

                    $metrics['supplier_fixed']++;
                    $supplierId = $referenceSupplierId;
                    $issueCodes = array_values(array_filter(
                        $issueCodes,
                        fn ($code) => $code !== 'supplier_missing_from_reference'
                    ));
                }

                if (empty($issueCodes)) {
                    $metrics['balanced']++;

                    continue;
                }

                $issues[] = [
                    'expense_id' => $expenseId,
                    'expense_number' => $expenseNumber,
                    'supplier_id' => $supplierId,
                    'purchase_order_id' => $purchaseOrderId,
                    'grn_id' => $grnId,
                    'issues' => implode(',', $issueCodes),
                ];
            }
        });

        if (! $json) {
            $this->line('');
            $this->info('Summary');
            $this->table(
                ['Metric', 'Value'],
                [
                    ['checked', $metrics['checked']],
                    ['balanced', $metrics['balanced']],
                    ['missing_purchase_order', $metrics['missing_purchase_order']],
                    ['missing_grn', $metrics['missing_grn']],
                    ['missing_supplier', $metrics['missing_supplier']],
                    ['supplier_mismatch', $metrics['supplier_mismatch']],
                    ['po_grn_supplier_conflict', $metrics['po_grn_supplier_conflict']],
                    ['supplier_autofixable', $metrics['supplier_autofixable']],
                    ['supplier_fixed', $metrics['supplier_fixed']],
                    ['unresolved_issues', count($issues)],
                ]
            );

            if (! empty($issues)) {
                $this->line('');
                $this->warn('Outstanding issues');
                $this->table(
                    ['expense_id', 'expense_number', 'supplier_id', 'purchase_order_id', 'grn_id', 'issues'],
                    array_slice($issues, 0, $maxIssues)
                );
            }
        }

        $exportPath = null;
        $exportOption = $this->option('export');
        if ($exportOption !== null && $exportOption !== false) {
            try {
                $exportPath = $this->resolveExportPath((string) $exportOption);
                $this->writeIssuesCsv($exportPath, $issues);
                if (! $json) {
                    $this->line('exported_issues_csv: '.$exportPath);
                }
            } catch (\Throwable $e) {
                return $this->emitError('Failed to write CSV export: '.$e->getMessage(), self::FAILURE, $json);
            }
        }

        $metrics['unresolved_issues'] = count($issues);
        $payload = [
            'ok' => empty($issues),
            'mode' => $apply ? 'apply' : 'dry-run',
            'strict' => $strict,
            'candidates' => $totalCandidates,
            'metrics' => $metrics,
            'issues_total' => count($issues),
            'issues_sample' => array_slice($issues, 0, $maxIssues),
            'exported_issues_csv' => $exportPath,
        ];
        if ($json) {
            $this->line((string) json_encode($payload));
        }

        if ($strict && ! empty($issues)) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function buildQuery(
        bool $hasSupplierColumn,
        bool $hasPurchaseOrderColumn,
        bool $hasGrnColumn,
        bool $hasExpenseDateColumn,
        bool $hasStatusColumn
    ): Builder {
        $query = DB::table('expenses')
            ->select(['id', 'expense_number']);

        if ($hasSupplierColumn) {
            $query->addSelect('supplier_id');
        }
        if ($hasPurchaseOrderColumn) {
            $query->addSelect('purchase_order_id');
        }
        if ($hasGrnColumn) {
            $query->addSelect('grn_id');
        }

        if ($hasSupplierColumn || $hasPurchaseOrderColumn || $hasGrnColumn) {
            $query->where(function ($builder) use ($hasSupplierColumn, $hasPurchaseOrderColumn, $hasGrnColumn): void {
                $hasAnyCondition = false;

                if ($hasSupplierColumn) {
                    $builder->whereNotNull('supplier_id');
                    $hasAnyCondition = true;
                }
                if ($hasPurchaseOrderColumn) {
                    if ($hasAnyCondition) {
                        $builder->orWhereNotNull('purchase_order_id');
                    } else {
                        $builder->whereNotNull('purchase_order_id');
                        $hasAnyCondition = true;
                    }
                }
                if ($hasGrnColumn) {
                    if ($hasAnyCondition) {
                        $builder->orWhereNotNull('grn_id');
                    } else {
                        $builder->whereNotNull('grn_id');
                    }
                }
            });
        }

        $expenseId = (int) ($this->option('expense_id') ?? 0);
        if ($expenseId > 0) {
            $query->where('id', $expenseId);
        }

        $supplierId = (int) ($this->option('supplier_id') ?? 0);
        if ($supplierId > 0 && $hasSupplierColumn) {
            $query->where('supplier_id', $supplierId);
        }

        if ($hasExpenseDateColumn) {
            $dateFrom = $this->parseDateOption('date_from');
            if ($dateFrom === false) {
                throw new \InvalidArgumentException('Invalid --date_from value. Use YYYY-MM-DD format.');
            }
            if (is_string($dateFrom)) {
                $query->whereDate('expense_date', '>=', $dateFrom);
            }

            $dateTo = $this->parseDateOption('date_to');
            if ($dateTo === false) {
                throw new \InvalidArgumentException('Invalid --date_to value. Use YYYY-MM-DD format.');
            }
            if (is_string($dateTo)) {
                $query->whereDate('expense_date', '<=', $dateTo);
            }
        }

        $status = (string) ($this->option('status') ?? '');
        if ($status !== '' && ! in_array($status, ['pending', 'approved', 'rejected', 'paid'], true)) {
            throw new \InvalidArgumentException('Invalid --status value. Use pending, approved, rejected, or paid.');
        }
        if ($status !== '' && $hasStatusColumn) {
            $query->where('status', $status);
        }

        return $query->orderBy('id');
    }

    private function parseDateOption(string $option): string|false|null
    {
        $value = $this->option($option);
        if (empty($value)) {
            return null;
        }

        try {
            return Carbon::parse((string) $value)->toDateString();
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $issues
     */
    private function writeIssuesCsv(string $absolutePath, array $issues): void
    {
        $directory = dirname($absolutePath);
        if (! is_dir($directory)) {
            if (! @mkdir($directory, 0777, true) && ! is_dir($directory)) {
                throw new \RuntimeException('Unable to create export directory: '.$directory);
            }
        }

        $handle = @fopen($absolutePath, 'wb');
        if ($handle === false) {
            throw new \RuntimeException('Unable to open export path: '.$absolutePath);
        }

        try {
            fputcsv($handle, [
                'expense_id',
                'expense_number',
                'supplier_id',
                'purchase_order_id',
                'grn_id',
                'issues',
            ]);

            foreach ($issues as $issue) {
                fputcsv($handle, [
                    (int) ($issue['expense_id'] ?? 0),
                    (string) ($issue['expense_number'] ?? ''),
                    (int) ($issue['supplier_id'] ?? 0),
                    (int) ($issue['purchase_order_id'] ?? 0),
                    (int) ($issue['grn_id'] ?? 0),
                    (string) ($issue['issues'] ?? ''),
                ]);
            }
        } finally {
            fclose($handle);
        }
    }

    private function resolveExportPath(string $option): string
    {
        $trimmed = trim($option);
        if ($trimmed === '' || in_array(strtolower($trimmed), ['1', 'true', 'auto'], true)) {
            return storage_path(
                'app/reconciliation/expense_procurement_reconcile_'.now()->format('Ymd_His').'.csv'
            );
        }

        $isAbsoluteWindows = (bool) preg_match('/^[a-zA-Z]:\\\\/', $trimmed);
        $isAbsoluteUnix = str_starts_with($trimmed, '/');

        $path = $trimmed;
        if (! $isAbsoluteWindows && ! $isAbsoluteUnix) {
            $path = storage_path('app/reconciliation/'.ltrim($trimmed, '\\/'));
        }

        if (strtolower(pathinfo($path, PATHINFO_EXTENSION)) !== 'csv') {
            $path .= '.csv';
        }

        return $path;
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
