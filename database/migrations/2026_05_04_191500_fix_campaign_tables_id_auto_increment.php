<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('campaigns')) {
            DB::statement('ALTER TABLE campaigns MODIFY id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT');
        }

        if (Schema::hasTable('campaign_reviews')) {
            DB::statement('ALTER TABLE campaign_reviews MODIFY id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT');
        }

        if (Schema::hasTable('campaign_product')) {
            DB::statement('ALTER TABLE campaign_product MODIFY id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('campaigns')) {
            DB::statement('ALTER TABLE campaigns MODIFY id INT(10) UNSIGNED NOT NULL');
        }

        if (Schema::hasTable('campaign_reviews')) {
            DB::statement('ALTER TABLE campaign_reviews MODIFY id INT(10) UNSIGNED NOT NULL');
        }

        if (Schema::hasTable('campaign_product')) {
            DB::statement('ALTER TABLE campaign_product MODIFY id BIGINT(20) UNSIGNED NOT NULL');
        }
    }
};
