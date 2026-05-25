<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_otps', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('order_id');
            $table->string('phone', 30);
            $table->string('purpose', 60)->default('order_confirmation');
            $table->string('otp_hash', 128);
            $table->unsignedTinyInteger('attempts_count')->default(0);
            $table->unsignedTinyInteger('resend_count')->default(0);
            $table->timestamp('last_sent_at')->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('verified_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'purpose', 'verified_at']);
            $table->index(['phone', 'purpose', 'expires_at']);
            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_otps');
    }
};
