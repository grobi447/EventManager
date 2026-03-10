<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mfa_secrets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                  ->unique()
                  ->constrained()
                  ->cascadeOnDelete();
            $table->string('secret', 512);
            $table->json('backup_codes');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mfa_secrets');
    }
};