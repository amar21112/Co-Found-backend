<?php
// database/migrations/2024_01_01_000008_create_skill_endorsements_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('skill_endorsements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_skill_id');
            $table->uuid('endorsed_by_user_id');
            $table->timestamps();

            $table->foreign('user_skill_id')->references('id')->on('user_skills')->onDelete('cascade');
            $table->foreign('endorsed_by_user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['user_skill_id', 'endorsed_by_user_id'], 'skill_endorsements_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('skill_endorsements');
    }
};
