<?php
// database/migrations/2024_01_01_000022_create_collaboration_ratings_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collaboration_ratings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('rater_id');
            $table->uuid('rated_user_id');
            $table->uuid('project_id')->nullable();
            $table->integer('communication_rating')->nullable()->comment('1 to 5 scale');
            $table->integer('reliability_rating')->nullable()->comment('1 to 5 scale');
            $table->integer('skill_rating')->nullable()->comment('1 to 5 scale');
            $table->integer('problem_solving_rating')->nullable()->comment('1 to 5 scale');
            $table->integer('teamwork_rating')->nullable()->comment('1 to 5 scale');
            $table->decimal('overall_rating', 3, 2)->nullable();
            $table->text('written_feedback')->nullable();
            $table->enum('visibility', ['public', 'private', 'anonymous'])->default('private');
            $table->timestamps();

            $table->foreign('rater_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('rated_user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
            $table->unique(['rater_id', 'rated_user_id', 'project_id'], 'collaboration_ratings_unique');
            $table->index('rated_user_id');
            $table->index('project_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collaboration_ratings');
    }
};
