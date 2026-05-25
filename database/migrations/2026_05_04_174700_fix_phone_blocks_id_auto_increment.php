<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('phone_blocks') || ! Schema::hasColumn('phone_blocks', 'id')) {
            return;
        }

        $column = DB::selectOne("SHOW COLUMNS FROM `phone_blocks` LIKE 'id'");

        if (! $column || str_contains(strtolower((string) $column->Extra), 'auto_increment')) {
            return;
        }

        if (empty($column->Key)) {
            DB::statement('ALTER TABLE `phone_blocks` ADD PRIMARY KEY (`id`)');
        }

        $type = (string) $column->Type;

        DB::statement("ALTER TABLE `phone_blocks` MODIFY `id` {$type} NOT NULL AUTO_INCREMENT");
    }

    public function down(): void
    {
        if (! Schema::hasTable('phone_blocks') || ! Schema::hasColumn('phone_blocks', 'id')) {
            return;
        }

        $column = DB::selectOne("SHOW COLUMNS FROM `phone_blocks` LIKE 'id'");

        if (! $column) {
            return;
        }

        $type = (string) $column->Type;

        DB::statement("ALTER TABLE `phone_blocks` MODIFY `id` {$type} NOT NULL");
    }
};
