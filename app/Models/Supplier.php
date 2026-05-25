<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Schema;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_code',
        'name',
        'email',
        'phone',
        'mobile',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'contact_person',
        'contact_person_phone',
        'contact_person_email',
        'notes',
        'status',
        'credit_limit',
        'payment_terms_days',
        'tax_id',
        'bank_name',
        'bank_account',
        'bank_routing',
    ];

    protected $casts = [
        'credit_limit' => 'decimal:2',
        'payment_terms_days' => 'integer',
    ];

    protected $appends = [
        'current_balance',
        'total_dues',
        'is_over_credit_limit',
        'payment_status',
        'performance_score',
    ];

    // Relationships

    public function openingBalances(): HasMany
    {
        return $this->hasMany(SupplierOpeningBalance::class);
    }

    public function latestOpeningBalance(): HasOne
    {
        return $this->hasOne(SupplierOpeningBalance::class)->latestOfMany();
    }

    public function ledger(): HasMany
    {
        return $this->hasMany(SupplierLedger::class)->orderBy('transaction_date', 'desc');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SupplierPayment::class)->orderBy('payment_date', 'desc');
    }

    public function purchaseReturns(): HasMany
    {
        return $this->hasMany(SupplierPurchaseReturn::class)->orderBy('return_date', 'desc');
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class)->orderByDesc('created_at');
    }

    // Accessors

    public function getCurrentBalanceAttribute(): float
    {
        try {
            if (array_key_exists('ledger_balance', $this->attributes)) {
                return (float) ($this->attributes['ledger_balance'] ?? 0);
            }

            return (float) ($this->ledger()->sum(\DB::raw('debit - credit')) ?? 0);
        } catch (\Exception $e) {
            return 0;
        }
    }

    public function getTotalDuesAttribute(): float
    {
        return max(0, $this->current_balance);
    }

    public function getIsOverCreditLimitAttribute(): bool
    {
        return $this->current_balance > (float) ($this->credit_limit ?? 0);
    }

    public function getPaymentStatusAttribute(): string
    {
        $balance = $this->current_balance;

        if ($balance <= 0) {
            return 'paid';
        }

        $daysOverdue = $this->getDaysOverdue();

        if ($daysOverdue > 90) {
            return 'critical';
        } elseif ($daysOverdue > 30) {
            return 'overdue';
        } elseif ($daysOverdue > 0) {
            return 'due_soon';
        }

        return 'current';
    }

    public function getPerformanceScoreAttribute(): float
    {
        try {
            $totalPurchases = (float) $this->ledger()->where('transaction_type', 'purchase')->sum('debit');
            $totalReturns = (float) $this->purchaseReturns()
                ->whereIn('status', ['approved', 'completed'])
                ->sum('total_amount');

            if ($totalPurchases <= 0.0) {
                return 100.0;
            }

            $totalPaymentsCount = (int) $this->payments()->count();
            $completedPayments = (int) $this->payments()->where('status', 'completed')->count();
            $completionRate = $totalPaymentsCount > 0
                ? (($completedPayments / $totalPaymentsCount) * 100)
                : 100;

            $returnRate = min(100, ($totalReturns / $totalPurchases) * 100);
            $returnScore = 100 - $returnRate;

            $daysOverdue = $this->getDaysOverdue();
            $timelinessScore = max(0, 100 - (min($daysOverdue, 120) * (100 / 120)));

            $score = ($returnScore * 0.4) + ($completionRate * 0.3) + ($timelinessScore * 0.3);

            return round(max(0, min(100, $score)), 2);
        } catch (\Exception $e) {
            return 100.0;
        }
    }

    // Helper Methods

    public function getDaysOverdue(): int
    {
        $outstandingDebits = $this->calculateOutstandingDebits();
        $oldestUnpaidTransaction = collect($outstandingDebits)->first();

        if (! $oldestUnpaidTransaction) {
            return 0;
        }

        $paymentTerms = max(0, (int) ($this->payment_terms_days ?? 0));
        $daysOverdue = now()->diffInDays($oldestUnpaidTransaction['date']) - $paymentTerms;

        return max(0, $daysOverdue);
    }

    public function getAgingSummary(): array
    {
        try {
            $paymentTerms = max(0, (int) ($this->payment_terms_days ?? 0));
            $current = 0;
            $overdue1_30 = 0;
            $overdue31_60 = 0;
            $overdue61_90 = 0;
            $overdue90_plus = 0;

            $outstandingDebits = $this->calculateOutstandingDebits();

            foreach ($outstandingDebits as $debit) {
                $daysDiff = now()->diffInDays($debit['date']);
                $balance = (float) $debit['remaining'];

                if ($daysDiff <= $paymentTerms) {
                    $current += $balance;
                } elseif ($daysDiff <= $paymentTerms + 30) {
                    $overdue1_30 += $balance;
                } elseif ($daysDiff <= $paymentTerms + 60) {
                    $overdue31_60 += $balance;
                } elseif ($daysDiff <= $paymentTerms + 90) {
                    $overdue61_90 += $balance;
                } else {
                    $overdue90_plus += $balance;
                }
            }

            return [
                'current' => $current,
                'overdue_1_30' => $overdue1_30,
                'overdue_31_60' => $overdue31_60,
                'overdue_61_90' => $overdue61_90,
                'overdue_90_plus' => $overdue90_plus,
                'total' => $current + $overdue1_30 + $overdue31_60 + $overdue61_90 + $overdue90_plus,
            ];
        } catch (\Exception $e) {
            // Return empty aging data if there's an error
            return [
                'current' => 0,
                'overdue_1_30' => 0,
                'overdue_31_60' => 0,
                'overdue_61_90' => 0,
                'overdue_90_plus' => 0,
                'total' => 0,
            ];
        }
    }

    public function addLedgerEntry(string $type, float $debit = 0, float $credit = 0, array $data = []): SupplierLedger
    {
        $lastBalance = $this->current_balance;
        $runningBalance = $lastBalance + $debit - $credit;
        $creatorId = isset($data['created_by']) ? (int) $data['created_by'] : (int) (auth()->id() ?? 0);

        if ($creatorId <= 0) {
            throw new \RuntimeException('Unable to create supplier ledger entry: no valid user context.');
        }

        $payload = [
            'transaction_date' => $data['transaction_date'] ?? now()->toDateString(),
            'transaction_type' => $type,
            'reference_type' => $data['reference_type'] ?? null,
            'reference_id' => $data['reference_id'] ?? null,
            'reference_number' => $data['reference_number'] ?? null,
            'description' => $data['description'] ?? '',
            'debit' => $debit,
            'credit' => $credit,
            'running_balance' => $runningBalance,
            'created_by' => $creatorId,
        ];

        if ($this->hasLedgerBranchColumn()) {
            $payload['branch_id'] = $data['branch_id'] ?? null;
        }

        return $this->ledger()->create($payload);
    }

    // Scopes

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeWithBalance($query)
    {
        return $query->with(['ledger' => function ($q) {
            $q->latest('transaction_date')->limit(1);
        }]);
    }

    public function scopeWithComputedBalance($query)
    {
        return $query->addSelect([
            'ledger_balance' => SupplierLedger::query()
                ->selectRaw('COALESCE(SUM(debit - credit), 0)')
                ->whereColumn('supplier_id', 'suppliers.id'),
        ]);
    }

    public function scopeOverCreditLimit($query)
    {
        return $query->whereRaw('(SELECT COALESCE(SUM(debit - credit), 0) FROM supplier_ledgers WHERE supplier_id = suppliers.id) > credit_limit');
    }

    public function scopeWithDues($query)
    {
        return $query->whereRaw('(SELECT COALESCE(SUM(debit - credit), 0) FROM supplier_ledgers WHERE supplier_id = suppliers.id) > 0');
    }

    /**
     * Build open debit positions after applying credits in FIFO order.
     */
    private function calculateOutstandingDebits(): array
    {
        if ($this->relationLoaded('ledger')) {
            $entries = $this->getRelation('ledger')
                ->sortBy(function ($entry) {
                    return sprintf(
                        '%s|%s|%s',
                        (string) $entry->transaction_date,
                        (string) $entry->created_at,
                        str_pad((string) $entry->id, 12, '0', STR_PAD_LEFT)
                    );
                })
                ->values();
        } else {
            $entries = $this->ledger()
                ->orderBy('transaction_date')
                ->orderBy('created_at')
                ->orderBy('id')
                ->get(['id', 'transaction_date', 'created_at', 'debit', 'credit']);
        }

        $openDebits = [];

        foreach ($entries as $entry) {
            $debit = (float) $entry->debit;
            $credit = (float) $entry->credit;

            if ($debit > 0) {
                $openDebits[] = [
                    'date' => $entry->transaction_date instanceof Carbon
                        ? $entry->transaction_date
                        : Carbon::parse($entry->transaction_date),
                    'remaining' => $debit,
                ];
            }

            if ($credit > 0) {
                $remainingCredit = $credit;

                foreach ($openDebits as &$openDebit) {
                    if ($remainingCredit <= 0) {
                        break;
                    }

                    if ($openDebit['remaining'] <= 0) {
                        continue;
                    }

                    $applied = min($openDebit['remaining'], $remainingCredit);
                    $openDebit['remaining'] -= $applied;
                    $remainingCredit -= $applied;
                }
                unset($openDebit);
            }
        }

        return array_values(array_filter($openDebits, static function (array $item) {
            return $item['remaining'] > 0;
        }));
    }

    private function hasLedgerBranchColumn(): bool
    {
        static $hasBranchColumn = null;

        if ($hasBranchColumn === null) {
            $table = (new SupplierLedger)->getTable();
            $hasBranchColumn = Schema::hasTable($table) && Schema::hasColumn($table, 'branch_id');
        }

        return (bool) $hasBranchColumn;
    }
}
