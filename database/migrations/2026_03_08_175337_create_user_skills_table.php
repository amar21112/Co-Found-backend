<?php
// database/migrations/2024_01_01_000007_create_user_skills_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_skills', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->string('skill_name', 100);
            $table->integer('proficiency_level')->comment('1 to 5 scale');
            $table->decimal('years_experience', 3, 1)->nullable();
            $table->boolean('is_approved')->default(false);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['user_id', 'skill_name']);
            $table->index('skill_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_skills');
    }
};
