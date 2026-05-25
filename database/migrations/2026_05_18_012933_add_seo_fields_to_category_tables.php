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
        Schema::table('categories', function (Blueprint $table) {
            if (!Schema::hasColumn('categories', 'meta_keyword')) {
                $table->string('meta_keyword')->nullable()->after('meta_description');
            }
        });

        Schema::table('subcategories', function (Blueprint $table) {
            if (!Schema::hasColumn('subcategories', 'meta_keyword')) {
                $table->string('meta_keyword')->nullable()->after('meta_description');
            }
        });

        Schema::table('childcategories', function (Blueprint $table) {
            if (!Schema::hasColumn('childcategories', 'meta_keyword')) {
                $table->string('meta_keyword')->nullable()->after('meta_description');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            if (Schema::hasColumn('categories', 'meta_keyword')) {
                $table->dropColumn('meta_keyword');
            }
        });

        Schema::table('subcategories', function (Blueprint $table) {
            if (Schema::hasColumn('subcategories', 'meta_keyword')) {
                $table->dropColumn('meta_keyword');
            }
        });

        Schema::table('childcategories', function (Blueprint $table) {
            if (Schema::hasColumn('childcategories', 'meta_keyword')) {
                $table->dropColumn('meta_keyword');
            }
        });
    }
};

