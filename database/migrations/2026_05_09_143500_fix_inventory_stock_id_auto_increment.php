<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->ensureAutoIncrementingId('inventories');
        $this->ensureAutoIncrementingId('warehouse_stock');
    }

    public function down(): void
    {
        $this->removeAutoIncrementingId('inventories');
        $this->removeAutoIncrementingId('warehouse_stock');
    }

    private function ensureAutoIncrementingId(string $table): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'id')) {
            return;
        }

        $column = DB::selectOne("SHOW COLUMNS FROM `{$table}` LIKE 'id'");

        if (! $column || str_contains(strtolower((string) $column->Extra), 'auto_increment')) {
            return;
        }

        if (empty($column->Key)) {
            DB::statement("ALTER TABLE `{$table}` ADD PRIMARY KEY (`id`)");
        }

        $type = (string) $column->Type;

        DB::statement("ALTER TABLE `{$table}` MODIFY `id` {$type} NOT NULL AUTO_INCREMENT");
    }

    private function removeAutoIncrementingId(string $table): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'id')) {
            return;
        }

        $column = DB::selectOne("SHOW COLUMNS FROM `{$table}` LIKE 'id'");

        if (! $column) {
            return;
        }

        $type = (string) $column->Type;

        DB::statement("ALTER TABLE `{$table}` MODIFY `id` {$type} NOT NULL");
    }
};
