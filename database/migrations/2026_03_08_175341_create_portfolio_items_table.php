<?php
// database/migrations/2024_01_01_000009_create_portfolio_items_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portfolio_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('file_url', 500)->nullable();
            $table->string('thumbnail_url', 500)->nullable();
            $table->enum('item_type', ['image', 'document', 'video', 'link', 'code']);
            $table->string('external_url', 500)->nullable();
            $table->enum('visibility', ['public', 'private', 'connections'])->default('public');
            $table->boolean('is_featured')->default(false);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['user_id', 'is_featured']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portfolio_items');
    }
};
