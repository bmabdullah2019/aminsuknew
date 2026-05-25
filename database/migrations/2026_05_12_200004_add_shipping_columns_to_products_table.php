<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'shipping_type')) {
                $table->string('shipping_type', 20)->nullable()->default(null)
                    ->comment('weight_based, fixed_rate, free_shipping, digital, or NULL for legacy');
            }

            if (! Schema::hasColumn('products', 'shipping_profile_id')) {
                $table->unsignedBigInteger('shipping_profile_id')->nullable()->default(null);
            }

            if (! Schema::hasColumn('products', 'fixed_shipping_cost')) {
                $table->integer('fixed_shipping_cost')->nullable()->default(null)
                    ->comment('Fixed shipping cost in major currency units');
            }

            if (! Schema::hasColumn('products', 'weight')) {
                $table->decimal('weight', 8, 3)->nullable()->default(null)
                    ->comment('Product weight in kg');
            }

            if (! Schema::hasColumn('products', 'length')) {
                $table->decimal('length', 8, 2)->nullable()->default(null)
                    ->comment('Product length in cm');
            }

            if (! Schema::hasColumn('products', 'width')) {
                $table->decimal('width', 8, 2)->nullable()->default(null)
                    ->comment('Product width in cm');
            }

            if (! Schema::hasColumn('products', 'height')) {
                $table->decimal('height', 8, 2)->nullable()->default(null)
                    ->comment('Product height in cm');
            }

            if (! Schema::hasColumn('products', 'is_physical')) {
                $table->boolean('is_physical')->default(true);
            }
        });

        // Add indexes and FK in a second call to avoid issues if column already exists
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'shipping_type')) {
                try {
                    $table->index('shipping_type', 'products_shipping_type_idx');
                } catch (\Throwable $e) {
                    // Index may already exist
                }
            }

            if (Schema::hasColumn('products', 'shipping_profile_id')) {
                try {
                    $table->foreign('shipping_profile_id')
                        ->references('id')
                        ->on('shipping_profiles')
                        ->onDelete('set null');
                } catch (\Throwable $e) {
                    // FK may already exist
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            try {
                $table->dropForeign(['shipping_profile_id']);
            } catch (\Throwable $e) {
            }

            try {
                $table->dropIndex('products_shipping_type_idx');
            } catch (\Throwable $e) {
            }

            $columnsToRemove = [
                'shipping_type',
                'shipping_profile_id',
                'fixed_shipping_cost',
                'weight',
                'length',
                'width',
                'height',
                'is_physical',
            ];

            foreach ($columnsToRemove as $column) {
                if (Schema::hasColumn('products', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
