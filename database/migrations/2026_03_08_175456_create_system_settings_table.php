<?php
// database/migrations/2024_01_01_000040_create_system_settings_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('setting_key')->unique();
            $table->json('setting_value');
            $table->string('setting_type', 50);
            $table->text('description')->nullable();
            $table->boolean('is_public')->default(false);
            $table->uuid('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            $table->index('setting_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
