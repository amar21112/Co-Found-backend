<?php
// database/migrations/2024_01_01_000020_create_matches_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('matches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->uuid('matched_user_id')->nullable();
            $table->uuid('matched_project_id')->nullable();
            $table->enum('match_type', ['collaborator', 'project']);
            $table->decimal('compatibility_score', 3, 2);
            $table->json('match_reasons')->nullable();
            $table->boolean('viewed')->default(false);
            $table->timestamp('viewed_at')->nullable();
            $table->boolean('saved')->default(false);
            $table->boolean('action_taken')->default(false);
            $table->timestamps();
            $table->timestamp('expires_at')->nullable();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('matched_user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('matched_project_id')->references('id')->on('projects')->onDelete('cascade');
            $table->index(['user_id', 'match_type', 'created_at']);
            $table->index(['user_id', 'viewed']);
            $table->index('matched_user_id');
            $table->index('matched_project_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matches');
    }
};
