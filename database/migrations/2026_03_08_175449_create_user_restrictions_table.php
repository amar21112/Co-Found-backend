<?php
// database/migrations/2024_01_01_000037_create_user_restrictions_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_restrictions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->uuid('restricted_by');
            $table->enum('restriction_type', ['warning', 'suspension', 'ban', 'content_restriction']);
            $table->text('reason');
            $table->integer('duration_hours')->nullable();
            $table->timestamp('starts_at')->useCurrent();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->uuid('lifted_by')->nullable();
            $table->timestamp('lifted_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('restricted_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('lifted_by')->references('id')->on('users')->onDelete('set null');
            $table->index(['user_id', 'is_active']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_restrictions');
    }
};
