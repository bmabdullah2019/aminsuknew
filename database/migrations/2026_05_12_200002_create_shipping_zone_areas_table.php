<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('shipping_zone_areas')) {
            return;
        }

        Schema::create('shipping_zone_areas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('shipping_zone_id');
            $table->string('area_name', 255);
            $table->unsignedInteger('shipping_charge_id')->nullable()->comment('Links to legacy shipping_charges for backward compat');
            $table->timestamps();

            $table->foreign('shipping_zone_id')
                ->references('id')
                ->on('shipping_zones')
                ->onDelete('cascade');

            $table->unique(['shipping_zone_id', 'area_name'], 'zone_area_unique');

            $table->index('shipping_charge_id', 'sza_charge_id_idx');
        });

        if (Schema::hasTable('shipping_charges')) {
            try {
                DB::statement('ALTER TABLE shipping_zone_areas ADD CONSTRAINT sza_shipping_charge_fk FOREIGN KEY (shipping_charge_id) REFERENCES shipping_charges(id) ON DELETE SET NULL');
            } catch (\Throwable $e) {
                // Some legacy databases have a non-standard shipping_charges id definition.
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_zone_areas');
    }
};
