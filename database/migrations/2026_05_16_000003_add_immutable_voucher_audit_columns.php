<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('accounts_transaction')) {
            return;
        }

        foreach ([
            'OriginalTranId' => 'INT NULL',
            'ReversalTranId' => 'INT NULL',
            'PostedAt' => 'DATETIME NULL',
            'PostedBy' => 'INT NULL',
            'ApprovedBy' => 'INT NULL',
            'ApprovedAt' => 'DATETIME NULL',
            'ApprovalStatus' => "VARCHAR(30) NULL DEFAULT 'approved'",
        ] as $column => $definition) {
            if (! Schema::hasColumn('accounts_transaction', $column)) {
                DB::statement("ALTER TABLE accounts_transaction ADD {$column} {$definition}");
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('accounts_transaction')) {
            return;
        }

        foreach (['ApprovalStatus', 'ApprovedAt', 'ApprovedBy', 'PostedBy', 'PostedAt', 'ReversalTranId', 'OriginalTranId'] as $column) {
            if (Schema::hasColumn('accounts_transaction', $column)) {
                DB::statement("ALTER TABLE accounts_transaction DROP COLUMN {$column}");
            }
        }
    }
};
