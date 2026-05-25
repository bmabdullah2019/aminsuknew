<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('document_sequences')) {
            return;
        }

        Schema::create('document_sequences', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('ComId')->default(0);
            $table->string('module', 80);
            $table->string('prefix', 40);
            $table->unsignedBigInteger('next_number')->default(1);
            $table->unsignedInteger('lock_version')->default(0);
            $table->timestamps();

            $table->unique(['ComId', 'module']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_sequences');
    }
};
