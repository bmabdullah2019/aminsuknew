<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('customers') || ! Schema::hasColumn('customers', 'id')) {
            return;
        }

        $column = DB::selectOne("SHOW COLUMNS FROM `customers` LIKE 'id'");

        if (! $column || str_contains(strtolower((string) $column->Extra), 'auto_increment')) {
            return;
        }

        if (empty($column->Key)) {
            DB::statement('ALTER TABLE `customers` ADD PRIMARY KEY (`id`)');
        }

        $type = (string) $column->Type;

        DB::statement("ALTER TABLE `customers` MODIFY `id` {$type} NOT NULL AUTO_INCREMENT");
    }

    public function down(): void
    {
        if (! Schema::hasTable('customers') || ! Schema::hasColumn('customers', 'id')) {
            return;
        }

        $column = DB::selectOne("SHOW COLUMNS FROM `customers` LIKE 'id'");

        if (! $column) {
            return;
        }

        $type = (string) $column->Type;

        DB::statement("ALTER TABLE `customers` MODIFY `id` {$type} NOT NULL");
    }
};
