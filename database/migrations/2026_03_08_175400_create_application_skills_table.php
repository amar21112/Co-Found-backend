<?php
// database/migrations/2024_01_01_000017_create_application_skills_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_skills', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('application_id');
            $table->string('skill_name', 100);
            $table->integer('proficiency_claimed')->comment('1 to 5 scale');

            $table->foreign('application_id')->references('id')->on('project_applications')->onDelete('cascade');
            $table->unique(['application_id', 'skill_name'], 'application_skills_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_skills');
    }
};
