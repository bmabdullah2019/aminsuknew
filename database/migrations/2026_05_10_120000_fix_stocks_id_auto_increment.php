<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fixes MySQL on `stocks`: AUTO_INCREMENT requires `id` to be the (or a) PRIMARY KEY.
 * Error #1075 / #1364: composite PRIMARY KEY on other columns while `id` is a plain INT.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        if (! Schema::hasTable('stocks') || ! Schema::hasColumn('stocks', 'id')) {
            return;
        }

        $column = DB::selectOne("SHOW COLUMNS FROM `stocks` LIKE 'id'");

        if (! $column || str_contains(strtolower((string) ($column->Extra ?? '')), 'auto_increment')) {
            return;
        }

        $primaryColumns = $this->primaryKeyColumns('stocks');
        $hadCompositeOrNonIdPk = $primaryColumns !== [] && $primaryColumns !== ['id'];

        if ($hadCompositeOrNonIdPk) {
            Schema::table('stocks', function (Blueprint $table): void {
                $table->dropPrimary();
            });
            $this->ensureBusinessUniqueIndex('stocks');
        }

        $idNeedsPrimaryKey = $hadCompositeOrNonIdPk
            || $primaryColumns === []
            || empty($column->Key);

        if ($idNeedsPrimaryKey) {
            DB::statement('ALTER TABLE `stocks` ADD PRIMARY KEY (`id`)');
        }

        $type = (string) $column->Type;
        DB::statement("ALTER TABLE `stocks` MODIFY `id` {$type} NOT NULL AUTO_INCREMENT");
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        if (! Schema::hasTable('stocks') || ! Schema::hasColumn('stocks', 'id')) {
            return;
        }

        $column = DB::selectOne("SHOW COLUMNS FROM `stocks` LIKE 'id'");

        if (! $column) {
            return;
        }

        $type = (string) $column->Type;
        DB::statement("ALTER TABLE `stocks` MODIFY `id` {$type} NOT NULL");
    }

    /**
     * @return list<string>
     */
    private function primaryKeyColumns(string $table): array
    {
        $db = DB::connection()->getDatabaseName();

        $rows = DB::select(
            'SELECT COLUMN_NAME AS col FROM information_schema.statistics
             WHERE table_schema = ? AND table_name = ? AND index_name = ?
             ORDER BY seq_in_index',
            [$db, $table, 'PRIMARY']
        );

        return array_values(array_map(static fn ($r) => (string) $r->col, $rows));
    }

    /**
     * Preserve uniqueness of business keys after dropping a composite PRIMARY KEY.
     */
    private function ensureBusinessUniqueIndex(string $table): void
    {
        if (! Schema::hasColumn($table, 'product_id')) {
            return;
        }

        $cols = ['product_id'];
        if (Schema::hasColumn($table, 'variant_id')) {
            $cols[] = 'variant_id';
        }
        if (Schema::hasColumn($table, 'branch_id')) {
            $cols[] = 'branch_id';
        }

        $indexName = 'stocks_product_variant_branch_unique';

        if ($this->indexExists($table, $indexName)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($cols, $indexName): void {
            $blueprint->unique($cols, $indexName);
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $db = DB::connection()->getDatabaseName();

        $found = DB::selectOne(
            'SELECT 1 AS ok FROM information_schema.statistics
             WHERE table_schema = ? AND table_name = ? AND index_name = ?
             LIMIT 1',
            [$db, $table, $indexName]
        );

        return $found !== null;
    }
};
