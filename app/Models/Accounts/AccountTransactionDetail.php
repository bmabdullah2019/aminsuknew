<?php

namespace App\Models\Accounts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountTransactionDetail extends Model
{
    protected $table = 'accounts_transaction_details';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'TranId', 'FiscalYearId', 'ComId', 'SubId', 'CustId', 'SupId',
        'EmpId', 'BatchId', 'TranParticular', 'PartNarration',
        'TranHead', 'Narration', 'Debit', 'Credit',
        'BankName', 'BranchName', 'ChequeNo', 'ChequeDate',
        'CreatedBy', 'CreatedAt', 'UpdatedBy', 'UpdatedAt',
        'DeletedBy', 'DeletedAt', 'Validity',
    ];

    protected $casts = [
        'TranId' => 'integer',
        'TranHead' => 'integer',
        'TranParticular' => 'integer',
        'SubId' => 'integer',
        'Debit' => 'decimal:2',
        'Credit' => 'decimal:2',
        'Validity' => 'boolean',
    ];

    public function scopeValid($query)
    {
        return $query->where('Validity', 1);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(AccountTransaction::class, 'TranId', 'TranId');
    }

    public function head(): BelongsTo
    {
        return $this->belongsTo(AccountHead::class, 'TranHead', 'HeadId');
    }

    public function particularHead(): BelongsTo
    {
        return $this->belongsTo(AccountHead::class, 'TranParticular', 'HeadId');
    }

    public function subsidiary(): BelongsTo
    {
        return $this->belongsTo(AccountSubsidiary::class, 'SubId', 'SubId');
    }

    /**
     * Create double-entry pairs from user-submitted voucher lines.
     *
     * @param  int  $tranId  Transaction header ID
     * @param  array  $lines  [{HeadId, SubId, Debit, Credit, Narration, BankName, BranchName, ChequeNo, ChequeDate}]
     * @return array The pairs to insert
     */
    public static function createDoubleEntryPairs(int $tranId, array $lines, int $fiscalYearId = 0, int $comId = 0): array
    {
        $user = auth()->user()->name ?? 'system';
        $now = now()->toDateTimeString();

        // Find the "head index" — the single debit or single credit line
        $debitLines = [];
        $creditLines = [];

        foreach ($lines as $k => $line) {
            if (($line['Debit'] ?? 0) > 0) {
                $debitLines[] = $k;
            }
            if (($line['Credit'] ?? 0) > 0) {
                $creditLines[] = $k;
            }
        }

        $headIndex = count($debitLines) === 1 ? $debitLines[0] : $creditLines[0];

        $pairs = [];

        foreach ($lines as $k => $line) {
            if ($k === $headIndex) {
                continue;
            }

            // Pair A: from current line's perspective
            $pairs[] = self::buildPairRow($tranId, $fiscalYearId, $comId, $line, $lines[$headIndex], $user, $now);

            // Pair B: from headIndex line's perspective (inverted)
            $pairs[] = self::buildPairRow(
                $tranId, $fiscalYearId, $comId,
                [
                    'HeadId' => $lines[$headIndex]['HeadId'],
                    'SubId' => $lines[$headIndex]['SubId'] ?? null,
                    'Debit' => $line['Credit'] ?? 0,
                    'Credit' => $line['Debit'] ?? 0,
                    'Narration' => $lines[$headIndex]['Narration'] ?? '',
                    'BankName' => $lines[$headIndex]['BankName'] ?? '',
                    'BranchName' => $lines[$headIndex]['BranchName'] ?? '',
                    'ChequeNo' => $lines[$headIndex]['ChequeNo'] ?? '',
                    'ChequeDate' => $lines[$headIndex]['ChequeDate'] ?? null,
                ],
                $line,
                $user,
                $now
            );
        }

        return $pairs;
    }

    private static function buildPairRow(int $tranId, int $fiscalYearId, int $comId, array $main, array $contra, string $user, string $now): array
    {
        return [
            'TranId' => $tranId,
            'FiscalYearId' => $fiscalYearId,
            'ComId' => $comId,
            'SubId' => $main['SubId'] ?? null,
            'CustId' => $main['CustId'] ?? null,
            'SupId' => $main['SupId'] ?? null,
            'EmpId' => $main['EmpId'] ?? null,
            'BatchId' => $main['BatchId'] ?? null,
            'TranHead' => $main['HeadId'],
            'TranParticular' => $contra['HeadId'],
            'Narration' => $main['Narration'] ?? '',
            'PartNarration' => $contra['Narration'] ?? '',
            'Debit' => $main['Debit'] ?? 0,
            'Credit' => $main['Credit'] ?? 0,
            'BankName' => $main['BankName'] ?? '',
            'BranchName' => $main['BranchName'] ?? '',
            'ChequeNo' => $main['ChequeNo'] ?? '',
            'ChequeDate' => ! empty($main['ChequeDate']) ? $main['ChequeDate'] : null,
            'CreatedBy' => $user,
            'CreatedAt' => $now,
            'Validity' => 1,
        ];
    }
}
