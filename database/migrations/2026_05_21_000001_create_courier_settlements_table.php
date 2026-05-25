<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('courier_settlements')) {
            Schema::create('courier_settlements', function (Blueprint $table): void {
                $table->id();
                $table->string('courier_type', 50);
                $table->string('courier_payment_id', 100)->nullable();
                $table->date('settlement_date')->nullable();
                $table->decimal('gross_cod_amount', 14, 2)->default(0);
                $table->decimal('delivery_charge', 14, 2)->default(0);
                $table->decimal('return_charge', 14, 2)->default(0);
                $table->decimal('adjustment_amount', 14, 2)->default(0);
                $table->decimal('net_receivable_amount', 14, 2)->default(0);
                $table->decimal('received_amount', 14, 2)->default(0);
                $table->string('status', 30)->default('synced');
                $table->json('raw_payload')->nullable();
                $table->timestamp('synced_at')->nullable();
                $table->timestamps();

                $table->unique(['courier_type', 'courier_payment_id'], 'courier_settlements_payment_unique');
                $table->index(['courier_type', 'settlement_date']);
                $table->index('status');
            });
        }

        if (! Schema::hasTable('courier_settlement_orders')) {
            Schema::create('courier_settlement_orders', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('courier_settlement_id')->constrained('courier_settlements')->cascadeOnDelete();
                // Legacy orders.id is unsigned integer in this project, so keep this nullable
                // indexed column without a hard FK to avoid cross-version type drift.
                $table->unsignedInteger('order_id')->nullable()->index();
                $table->unsignedBigInteger('consignment_id')->nullable();
                $table->string('tracking_code', 100)->nullable();
                $table->string('invoice_id', 100)->nullable();
                $table->decimal('cod_amount', 14, 2)->default(0);
                $table->decimal('delivery_charge', 14, 2)->default(0);
                $table->decimal('return_charge', 14, 2)->default(0);
                $table->decimal('adjustment_amount', 14, 2)->default(0);
                $table->decimal('net_amount', 14, 2)->default(0);
                $table->string('delivery_status', 60)->nullable();
                $table->json('raw_payload')->nullable();
                $table->timestamps();

                $table->unique(['courier_settlement_id', 'consignment_id'], 'courier_settlement_order_consignment_unique');
                $table->index(['invoice_id', 'tracking_code']);
                $table->index('delivery_status');
            });
        }

        $this->ensureCourierAccount('courier_receivable', 'Courier Receivable', 1);
        $this->ensureCourierAccount('courier_delivery_expense', 'Courier Delivery Expense', 5);
        $this->ensureCourierAccount('courier_adjustment_expense', 'Courier Adjustment Expense', 5);
    }

    public function down(): void
    {
        Schema::dropIfExists('courier_settlement_orders');
        Schema::dropIfExists('courier_settlements');
    }

    private function ensureCourierAccount(string $code, string $name, int $accType): void
    {
        if (! Schema::hasTable('accounts_head')) {
            return;
        }

        if (DB::table('accounts_head')->where('HeadCode', $code)->exists()) {
            return;
        }

        $parentId = (int) (DB::table('accounts_head')
            ->where('AccType', $accType)
            ->where('ParentId', 0)
            ->where('Validity', 1)
            ->value('HeadId') ?? 0);

        if ($parentId <= 0) {
            $parentId = (int) (DB::table('accounts_head')
                ->where('AccType', $accType)
                ->where('Validity', 1)
                ->value('HeadId') ?? 0);
        }

        DB::table('accounts_head')->insert([
            'ParentId' => $parentId,
            'AccType' => $accType,
            'HeadCode' => $code,
            'HeadName' => $name,
            'Label' => $parentId > 0 ? 2 : 1,
            'HasChild' => 0,
            'ParentHead' => 0,
            'Description' => 'System account for courier accounting automation.',
            'CreatedBy' => 'system',
            'CreatedAt' => now(),
            'Validity' => 1,
        ]);
    }
};
