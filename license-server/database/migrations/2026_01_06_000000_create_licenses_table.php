<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('licenses', function (Blueprint $table) {
            $table->id();
            $table->string('domain')->unique();

            // Store encrypted at rest via model cast.
            $table->text('license_key');

            $table->enum('status', ['active', 'inactive', 'suspended'])->default('inactive');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('last_checked_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('licenses');
    }
};
