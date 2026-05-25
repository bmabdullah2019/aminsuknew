<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('steadfast_consignment_id')->nullable()->after('order_status');
            $table->string('steadfast_tracking_code', 50)->nullable()->after('steadfast_consignment_id');
            $table->string('steadfast_status', 60)->nullable()->after('steadfast_tracking_code');

            $table->index('steadfast_consignment_id');
            $table->index('steadfast_tracking_code');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['steadfast_consignment_id']);
            $table->dropIndex(['steadfast_tracking_code']);
            $table->dropColumn(['steadfast_consignment_id', 'steadfast_tracking_code', 'steadfast_status']);
        });
    }
};
