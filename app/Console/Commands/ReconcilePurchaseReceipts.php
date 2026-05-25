<?php

namespace App\Console\Commands;

use App\Models\PurchaseOrder;
use App\Models\User;
use App\Services\SupplierService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ReconcilePurchaseReceipts extends Command
{
    /**
     * @var string
     */
    protected $signature = 'purchase-orders:reconcile-receipts
        {--po_id= : Specific purchase order ID}
        {--supplier_id= : Filter by supplier ID}
        {--date_from= : Filter purchase orders created on/after this date (YYYY-MM-DD)}
        {--date_to= : Filter purchase orders created on/before this date (YYYY-MM-DD)}
        {--apply : Apply missing supplier payable postings and snapshot updates}
        {--strict : Exit with failure code when unresolved mismatches remain}
        {--json : Output machine-readable JSON summary}
        {--max_issues=200 : Maximum mismatches to print in output table}';

    /**
     * @var string
     */
    protected $description = 'Reconcile purchase receipt payables with supplier ledger and purchase order snapshots';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $strict = (bool) $this->option('strict');
        $json = (bool) $this->option('json');
        $maxIssues = (int) ($this->option('max_issues') ?? 200);
        if ($maxIssues <= 0) {
            return $this->emitError('Invalid --max_issues value. Use a positive integer.', self::INVALID, $json);
        }

        $supplierService = app(SupplierService::class);

        $query = PurchaseOrder::query()
            ->with('supplier')
            ->whereHas('purchaseItems', function ($builder) {
                $builder->where('quantity_received', '>', 0);
            })
            ->orderBy('id');

        $poId = (int) ($this->option('po_id') ?? 0);
        if ($poId > 0) {
            $query->whereKey($poId);
        }

        $supplierId = (int) ($this->option('supplier_id') ?? 0);
        if ($supplierId > 0) {
            $query->where('supplier_id', $supplierId);
        }

        $dateFrom = $this->parseDateOption('date_from');
        if ($dateFrom === false) {
            return $this->emitError('Invalid --date_from value. Use YYYY-MM-DD format.', self::INVALID, $json);
        }
        if (is_string($dateFrom)) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        $dateTo = $this->parseDateOption('date_to');
        if ($dateTo === false) {
            return $this->emitError('Invalid --date_to value. Use YYYY-MM-DD format.', self::INVALID, $json);
        }
        if (is_string($dateTo)) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $totalCandidates = (clone $query)->count();
        if (! $json) {
            $this->info('Reconciling purchase receipt payables...');
            $this->line('mode: '.($apply ? 'apply' : 'dry-run'));
            $this->line('strict: '.($strict ? 'yes' : 'no'));
            $this->line('candidates: '.$totalCandidates);
        }

        $hasReceivedAtColumn = Schema::hasTable('purchase_orders')
            && Schema::hasColumn('purchase_orders', 'received_at');
        $hasLedgerPostedAmountColumn = Schema::hasTable('purchase_orders')
            && Schema::hasColumn('purchase_orders', 'ledger_posted_amount');

        $actorId = (int) (auth()->id() ?? User::query()->value('id') ?? 0);
        if ($apply && $actorId <= 0) {
            return $this->emitError(
                'Cannot apply reconciliation: no valid user found for created_by.',
                self::FAILURE,
                $json
            );
        }

        $metrics = [
            'checked' => 0,
            'balanced' => 0,
            'under_posted' => 0,
            'over_posted' => 0,
            'fixed' => 0,
            'target_total' => 0.0,
            'posted_before_total' => 0.0,
            'posted_after_total' => 0.0,
        ];
        $issues = [];

        $query->chunkById(200, function ($purchaseOrders) use (
            $supplierService,
            $apply,
            $actorId,
            $hasReceivedAtColumn,
            $hasLedgerPostedAmountColumn,
            &$metrics,
            &$issues
        ): void {
            foreach ($purchaseOrders as $purchaseOrder) {
                $metrics['checked']++;

                $targetAmount = (float) $purchaseOrder->purchaseItems()
                    ->selectRaw('COALESCE(SUM(quantity_received * unit_cost), 0) AS total_received_amount')
                    ->value('total_received_amount');
                $targetAmount = round($targetAmount, 2);

                $postedBefore = $purchaseOrder->supplier_id
                    ? round($supplierService->getPostedPurchaseReceiptAmount((int) $purchaseOrder->supplier_id, (int) $purchaseOrder->id), 2)
                    : 0.0;
                $deltaBefore = round($targetAmount - $postedBefore, 2);
                $statusBefore = $this->determineStatus($deltaBefore);

                $metrics['target_total'] += $targetAmount;
                $metrics['posted_before_total'] += $postedBefore;
                $metrics[$statusBefore]++;

                $postedAfter = $postedBefore;
                $deltaAfter = $deltaBefore;
                $statusAfter = $statusBefore;
                $applied = false;

                if ($apply && $statusBefore === 'under_posted' && $purchaseOrder->supplier) {
                    $supplierService->syncPurchaseReceiptLedger($purchaseOrder->supplier, [
                        'target_received_amount' => $targetAmount,
                        'purchase_date' => now()->toDateString(),
                        'purchase_id' => (int) $purchaseOrder->id,
                        'purchase_number' => $purchaseOrder->po_number,
                        'reference_type' => 'purchase_receipt',
                        'description' => "Reconciliation posting for PO #{$purchaseOrder->po_number}",
                        'created_by' => $actorId,
                    ]);
                    $applied = true;
                }

                if ($apply) {
                    $postedAfter = $purchaseOrder->supplier_id
                        ? round($supplierService->getPostedPurchaseReceiptAmount((int) $purchaseOrder->supplier_id, (int) $purchaseOrder->id), 2)
                        : 0.0;
                    $deltaAfter = round($targetAmount - $postedAfter, 2);
                    $statusAfter = $this->determineStatus($deltaAfter);

                    $snapshotPayload = [];
                    if ($hasLedgerPostedAmountColumn) {
                        $snapshotPayload['ledger_posted_amount'] = $postedAfter;
                    }
                    if ($hasReceivedAtColumn && $purchaseOrder->status === 'received' && empty($purchaseOrder->received_at)) {
                        $snapshotPayload['received_at'] = now();
                    }
                    if (! empty($snapshotPayload)) {
                        DB::table('purchase_orders')
                            ->where('id', $purchaseOrder->id)
                            ->update($snapshotPayload);
                    }
                }

                $metrics['posted_after_total'] += $postedAfter;
                if ($applied && $statusAfter === 'balanced') {
                    $metrics['fixed']++;
                }

                if ($statusAfter !== 'balanced') {
                    $issues[] = [
                        'po_id' => (int) $purchaseOrder->id,
                        'po_number' => (string) $purchaseOrder->po_number,
                        'supplier_id' => (int) ($purchaseOrder->supplier_id ?? 0),
                        'target' => $targetAmount,
                        'posted' => $postedAfter,
                        'delta' => $deltaAfter,
                        'status' => $statusAfter,
                    ];
                }
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
                    ['under_posted', $metrics['under_posted']],
                    ['over_posted', $metrics['over_posted']],
                    ['fixed', $metrics['fixed']],
                    ['unresolved_issues', count($issues)],
                    ['target_total', round($metrics['target_total'], 2)],
                    ['posted_before_total', round($metrics['posted_before_total'], 2)],
                    ['posted_after_total', round($metrics['posted_after_total'], 2)],
                ]
            );

            if (! empty($issues)) {
                $this->line('');
                $this->warn('Outstanding mismatches');
                $this->table(
                    ['po_id', 'po_number', 'supplier_id', 'target', 'posted', 'delta', 'status'],
                    array_slice($issues, 0, $maxIssues)
                );
            }
        }

        $metrics['unresolved_issues'] = count($issues);
        if ($json) {
            $this->line((string) json_encode([
                'ok' => empty($issues),
                'mode' => $apply ? 'apply' : 'dry-run',
                'strict' => $strict,
                'candidates' => $totalCandidates,
                'metrics' => $metrics,
                'issues_total' => count($issues),
                'issues_sample' => array_slice($issues, 0, $maxIssues),
            ]));
        }

        if ($strict && ! empty($issues)) {
            return self::FAILURE;
        }

        return self::SUCCESS;
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

    private function determineStatus(float $delta): string
    {
        if (abs($delta) <= 0.01) {
            return 'balanced';
        }

        return $delta > 0 ? 'under_posted' : 'over_posted';
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
