<?php
// database/migrations/2024_01_01_000011_create_projects_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('owner_id');
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('short_description', 500);
            $table->text('full_description');
            $table->string('category', 100);
            $table->enum('status', ['planning', 'active', 'on_hold', 'completed', 'cancelled'])
                ->default('planning');
            $table->enum('visibility', ['public', 'private', 'unlisted'])->default('public');
            $table->integer('team_size_min')->nullable();
            $table->integer('team_size_max')->nullable();
            $table->integer('current_team_size')->default(0);
            $table->date('start_date')->nullable();
            $table->date('target_completion_date')->nullable();
            $table->date('actual_completion_date')->nullable();
            $table->boolean('is_accepting_applications')->default(true);
            $table->date('application_deadline')->nullable();
            $table->integer('view_count')->default(0);
            $table->integer('application_count')->default(0);
            $table->timestamps();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('archived_at')->nullable();

            $table->foreign('owner_id')->references('id')->on('users')->onDelete('cascade');
            $table->index('slug');
            $table->index('owner_id');
            $table->index('status');
            $table->index('category');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
