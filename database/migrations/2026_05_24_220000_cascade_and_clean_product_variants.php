<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the missing foreign-key cascade between products -> product_variants ->
 * variant_images / product_variant_attribute_values, and cleans up orphan rows
 * left behind by previous deletes that bypassed application-level cleanup.
 *
 * Without these constraints, MySQL was free to reissue an auto-increment
 * `products.id` (after a hard delete) and silently re-attach an old orphan
 * variant to a brand-new product, surfacing as a "phantom variant with the
 * wrong image" on the storefront.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            // 1) Delete orphan attribute-value rows whose parent variant is gone.
            DB::statement('
                DELETE pvav FROM product_variant_attribute_values pvav
                LEFT JOIN product_variants pv ON pv.id = pvav.product_variant_id
                WHERE pv.id IS NULL
            ');

            // 2) Delete orphan variant images whose parent variant is gone.
            DB::statement('
                DELETE vi FROM variant_images vi
                LEFT JOIN product_variants pv ON pv.id = vi.product_variant_id
                WHERE pv.id IS NULL
            ');

            // 3) Delete orphan variants whose parent product is gone, and
            //    cascade-delete their children first (no FK to rely on yet).
            $orphanVariantIds = DB::table('product_variants as pv')
                ->leftJoin('products as p', 'p.id', '=', 'pv.product_id')
                ->whereNull('p.id')
                ->pluck('pv.id')
                ->all();

            if (! empty($orphanVariantIds)) {
                DB::table('product_variant_attribute_values')
                    ->whereIn('product_variant_id', $orphanVariantIds)
                    ->delete();
                DB::table('variant_images')
                    ->whereIn('product_variant_id', $orphanVariantIds)
                    ->delete();
                if (Schema::hasTable('warehouse_stocks')) {
                    DB::table('warehouse_stocks')
                        ->whereIn('product_variant_id', $orphanVariantIds)
                        ->delete();
                }
                if (Schema::hasTable('inventories')) {
                    DB::table('inventories')
                        ->whereIn('product_variant_id', $orphanVariantIds)
                        ->delete();
                }
                DB::table('product_variants')
                    ->whereIn('id', $orphanVariantIds)
                    ->delete();
            }
        });

        // 4) Add the foreign keys with ON DELETE CASCADE so this can never
        //    happen again. Drop any pre-existing FK with the same name first
        //    (best-effort) to keep the migration idempotent across reruns.
        $this->addForeignKeyIfMissing(
            'product_variants',
            'product_variants_product_id_foreign',
            'product_id',
            'products',
            'id'
        );

        $this->addForeignKeyIfMissing(
            'variant_images',
            'variant_images_product_variant_id_foreign',
            'product_variant_id',
            'product_variants',
            'id'
        );

        $this->addForeignKeyIfMissing(
            'product_variant_attribute_values',
            'product_variant_attribute_values_product_variant_id_foreign',
            'product_variant_id',
            'product_variants',
            'id'
        );
    }

    public function down(): void
    {
        $this->dropForeignKeyIfExists('product_variant_attribute_values', 'product_variant_attribute_values_product_variant_id_foreign');
        $this->dropForeignKeyIfExists('variant_images', 'variant_images_product_variant_id_foreign');
        $this->dropForeignKeyIfExists('product_variants', 'product_variants_product_id_foreign');
    }

    private function addForeignKeyIfMissing(string $table, string $name, string $column, string $refTable, string $refColumn): void
    {
        $exists = DB::selectOne('
            SELECT COUNT(*) AS n
            FROM information_schema.TABLE_CONSTRAINTS
            WHERE CONSTRAINT_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND CONSTRAINT_NAME = ?
              AND CONSTRAINT_TYPE = "FOREIGN KEY"
        ', [$table, $name]);

        if ((int) ($exists->n ?? 0) > 0) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($column, $refColumn, $refTable, $name) {
            $blueprint->foreign($column, $name)
                ->references($refColumn)
                ->on($refTable)
                ->cascadeOnDelete();
        });
    }

    private function dropForeignKeyIfExists(string $table, string $name): void
    {
        $exists = DB::selectOne('
            SELECT COUNT(*) AS n
            FROM information_schema.TABLE_CONSTRAINTS
            WHERE CONSTRAINT_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND CONSTRAINT_NAME = ?
              AND CONSTRAINT_TYPE = "FOREIGN KEY"
        ', [$table, $name]);

        if ((int) ($exists->n ?? 0) === 0) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($name) {
            $blueprint->dropForeign($name);
        });
    }
};
