<?php
// database/migrations/2024_01_01_000041_create_configuration_history_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('configuration_history', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('setting_key');
            $table->json('old_value')->nullable();
            $table->json('new_value')->nullable();
            $table->uuid('changed_by');
            $table->text('change_reason')->nullable();
            $table->timestamps();

            $table->foreign('changed_by')->references('id')->on('users')->onDelete('cascade');
            $table->index('setting_key');
            $table->index('changed_by');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('configuration_history');
    }
};
