<?php
// database/migrations/2024_01_01_000013_create_project_roles_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_roles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('project_id');
            $table->string('role_name', 100);
            $table->text('description')->nullable();
            $table->integer('positions_needed')->default(1);
            $table->integer('positions_filled')->default(0);
            $table->timestamps();

            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
            $table->unique(['project_id', 'role_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_roles');
    }
};
