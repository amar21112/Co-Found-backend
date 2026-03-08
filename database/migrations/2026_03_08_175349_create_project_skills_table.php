<?php
// database/migrations/2024_01_01_000012_create_project_skills_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_skills', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('project_id');
            $table->string('skill_name', 100);
            $table->integer('proficiency_required')->comment('1 to 5 scale');
            $table->integer('positions_needed')->default(1);
            $table->integer('positions_filled')->default(0);
            $table->boolean('is_required')->default(true);

            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
            $table->unique(['project_id', 'skill_name']);
            $table->index('skill_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_skills');
    }
};
