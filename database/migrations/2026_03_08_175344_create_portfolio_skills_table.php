<?php
// database/migrations/2024_01_01_000010_create_portfolio_skills_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portfolio_skills', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('portfolio_item_id');
            $table->string('skill_name', 100);

            $table->foreign('portfolio_item_id')->references('id')->on('portfolio_items')->onDelete('cascade');
            $table->unique(['portfolio_item_id', 'skill_name'], 'portfolio_skills_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portfolio_skills');
    }
};
