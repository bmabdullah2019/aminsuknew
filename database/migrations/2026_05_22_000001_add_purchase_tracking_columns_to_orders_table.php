<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'purchase_tracking_status')) {
                $table->string('purchase_tracking_status', 30)->default('pending')->after('purchase_pixel_fired_at');
            }
            if (!Schema::hasColumn('orders', 'purchase_tracked_at')) {
                $table->timestamp('purchase_tracked_at')->nullable()->after('purchase_tracking_status');
            }
            if (!Schema::hasColumn('orders', 'tracking_provider_status')) {
                $table->json('tracking_provider_status')->nullable()->after('purchase_tracked_at');
            }
            
            $table->index(['purchase_tracking_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['purchase_tracking_status']);
            $table->dropColumn([
                'purchase_tracking_status',
                'purchase_tracked_at',
                'tracking_provider_status',
            ]);
        });
    }
};
