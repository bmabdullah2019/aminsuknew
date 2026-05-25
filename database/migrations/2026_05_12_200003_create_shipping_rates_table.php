<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('shipping_rates')) {
            return;
        }

        Schema::create('shipping_rates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('shipping_zone_id');
            $table->unsignedBigInteger('shipping_profile_id');
            $table->decimal('min_weight', 8, 3)->default(0);
            $table->decimal('max_weight', 8, 3)->default(0);
            $table->integer('rate')->default(0)->comment('Amount in major currency units');
            $table->unsignedBigInteger('rate_minor')->default(0)->comment('Amount in minor units (paisa)');
            $table->string('currency', 3)->default('BDT');
            $table->tinyInteger('status')->default(1);
            $table->timestamps();

            $table->foreign('shipping_zone_id')
                ->references('id')
                ->on('shipping_zones')
                ->onDelete('cascade');

            $table->foreign('shipping_profile_id')
                ->references('id')
                ->on('shipping_profiles')
                ->onDelete('cascade');

            $table->unique(
                ['shipping_zone_id', 'shipping_profile_id', 'min_weight', 'max_weight'],
                'rate_zone_profile_weight_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_rates');
    }
};
