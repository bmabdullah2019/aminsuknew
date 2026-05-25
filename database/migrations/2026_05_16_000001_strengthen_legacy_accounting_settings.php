<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('accounts_settings')) {
            foreach (['SalesReturn', 'VATPayable', 'DiscountAllowed'] as $column) {
                if (! Schema::hasColumn('accounts_settings', $column)) {
                    DB::statement("ALTER TABLE accounts_settings ADD {$column} INT NULL");
                }
            }
        }

        if (Schema::hasTable('accounts_transaction_details') && DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE accounts_transaction_details MODIFY Debit DECIMAL(18,4) NULL');
            DB::statement('ALTER TABLE accounts_transaction_details MODIFY Credit DECIMAL(18,4) NULL');
        }

        if (Schema::hasTable('accounts_transaction') && DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE accounts_transaction MODIFY TranAmount DECIMAL(18,4) NULL');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('accounts_settings')) {
            foreach (['DiscountAllowed', 'VATPayable', 'SalesReturn'] as $column) {
                if (Schema::hasColumn('accounts_settings', $column)) {
                    DB::statement("ALTER TABLE accounts_settings DROP COLUMN {$column}");
                }
            }
        }
    }
};
