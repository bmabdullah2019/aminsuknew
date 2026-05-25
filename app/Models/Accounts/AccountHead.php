<?php

namespace App\Models\Accounts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class AccountHead extends Model
{
    use LogsActivity;

    protected $table = 'accounts_head';

    protected $primaryKey = 'HeadId';

    public $timestamps = false;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll()->logOnlyDirty();
    }

    protected $fillable = [
        'ParentId', 'AccType', 'HeadCode', 'HeadName', 'Label',
        'HasChild', 'ParentHead', 'Description',
        'CreatedBy', 'CreatedAt', 'UpdatedBy', 'UpdatedAt',
        'DeletedBy', 'DeletedAt', 'Validity',
    ];

    protected $casts = [
        'HeadId' => 'integer',
        'ParentId' => 'integer',
        'AccType' => 'integer',
        'Label' => 'integer',
        'HasChild' => 'boolean',
        'Validity' => 'boolean',
    ];

    // ── Scopes ──

    public function scopeValid($query)
    {
        return $query->where('Validity', 1);
    }

    public function scopeRoots($query)
    {
        return $query->where('ParentId', 0);
    }

    public function scopeLeaves($query)
    {
        return $query->where('HasChild', 0);
    }

    // ── Relationships ──

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'ParentId', 'HeadId');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'ParentId', 'HeadId')->where('Validity', 1);
    }

    public function subsidiaryHeads(): HasMany
    {
        return $this->hasMany(AccountSubsidiaryHead::class, 'HeadId', 'HeadId');
    }

    public function openingBalances(): HasMany
    {
        return $this->hasMany(AccountOpening::class, 'TranHead', 'HeadId');
    }

    public function transactionDetails(): HasMany
    {
        return $this->hasMany(AccountTransactionDetail::class, 'TranHead', 'HeadId');
    }

    // ── Code Generation ──

    public static function getNewCode(int $parentId): string
    {
        if ($parentId === 0) {
            $numericRootCodes = self::valid()
                ->roots()
                ->pluck('HeadCode')
                ->filter(fn ($code) => is_string($code) && preg_match('/^\d+$/', $code));

            $max = $numericRootCodes
                ->map(fn ($code) => (int) $code)
                ->max();

            return (string) (($max ?: 0) + 1);
        }

        $parent = self::valid()->findOrFail($parentId);
        $prefix = trim((string) $parent->HeadCode);
        $nextLabel = self::nextLabelForParent($parentId);

        $children = self::valid()
            ->where('ParentId', $parentId)
            ->pluck('HeadCode');

        $segments = $children
            ->map(fn ($code) => self::extractDirectChildSegment($prefix, (string) $code))
            ->filter(fn ($segment) => $segment !== null)
            ->values();

        $numericSegments = $segments
            ->filter(fn ($segment) => preg_match('/^\d+$/', $segment))
            ->values();

        $nextSeq = $numericSegments->isEmpty()
            ? 1
            : $numericSegments->map(fn ($segment) => (int) $segment)->max() + 1;

        $segmentWidth = $numericSegments->isEmpty()
            ? self::defaultSegmentWidth($nextLabel)
            : $numericSegments->map(fn ($segment) => strlen((string) $segment))->max();

        $nextSegment = $segmentWidth > 1
            ? str_pad((string) $nextSeq, $segmentWidth, '0', STR_PAD_LEFT)
            : (string) $nextSeq;

        return $prefix.'.'.$nextSegment;
    }

    public static function nextLabelForParent(int $parentId): int
    {
        if ($parentId === 0) {
            return 1;
        }

        $parent = self::valid()->findOrFail($parentId);
        $labelFromColumn = (int) ($parent->Label ?? 0);
        $labelFromCode = self::codeDepth((string) $parent->HeadCode);

        return max($labelFromColumn, $labelFromCode) + 1;
    }

    private static function extractDirectChildSegment(string $prefix, string $code): ?string
    {
        $childPrefix = $prefix.'.';
        if (! str_starts_with($code, $childPrefix)) {
            return null;
        }

        $remainder = substr($code, strlen($childPrefix));

        if ($remainder === '' || str_contains($remainder, '.')) {
            return null;
        }

        return $remainder;
    }

    private static function defaultSegmentWidth(int $label): int
    {
        return $label <= 2 ? 1 : 3;
    }

    private static function codeDepth(string $code): int
    {
        $trimmed = trim($code);

        if ($trimmed === '') {
            return 0;
        }

        return substr_count($trimmed, '.') + 1;
    }

    // ── Tree Rebuild ──

    public static function rebuildTree(): void
    {
        DB::table('accounts_tree')->truncate();
        self::insertTreeChildren(0, 1);
    }

    private static function insertTreeChildren(int $parentId, int $serial): int
    {
        $children = self::valid()
            ->where('ParentId', $parentId)
            ->get()
            ->sortBy(fn (self $head) => (string) $head->HeadCode, SORT_NATURAL)
            ->values();

        foreach ($children as $child) {
            DB::table('accounts_tree')->insert([
                'Serial' => $serial,
                'HeadId' => $child->HeadId,
                'ParentId' => $child->ParentId,
                'AccType' => $child->AccType,
                'HeadCode' => $child->HeadCode,
                'HeadName' => $child->HeadName,
                'Label' => $child->Label,
                'HasChild' => $child->HasChild,
                'ParentHead' => $child->ParentHead,
                'Description' => $child->Description,
                'Validity' => $child->Validity,
                'CreatedBy' => $child->CreatedBy,
                'CreatedAt' => $child->CreatedAt,
            ]);
            $serial++;

            if ($child->HasChild) {
                $serial = self::insertTreeChildren($child->HeadId, $serial);
            }
        }

        return $serial;
    }

    // ── Helper: Get all leaf HeadIds under this head recursively ──

    public function getAllLeafIds(): array
    {
        $ids = [];
        $this->collectLeafIds($this->HeadId, $ids);

        return $ids;
    }

    private function collectLeafIds(int $headId, array &$ids): void
    {
        $children = self::valid()->where('ParentId', $headId)->get();

        if ($children->isEmpty()) {
            $ids[] = $headId;

            return;
        }

        foreach ($children as $child) {
            $this->collectLeafIds($child->HeadId, $ids);
        }
    }

    // ── Helper: Build breadcrumb ──

    public static function buildBreadcrumb(int $parentId): string
    {
        $parts = [];
        $current = $parentId;

        while ($current > 0) {
            $head = self::find($current);
            if (! $head) {
                break;
            }
            $parts[] = $head->HeadName;
            $current = $head->ParentId;
        }

        return implode(' / ', array_reverse($parts)).($parts ? ' /' : '');
    }
}
