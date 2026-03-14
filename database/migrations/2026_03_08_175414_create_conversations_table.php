<?php
// database/migrations/2024_01_01_000023_create_conversations_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->enum('conversation_type', ['direct', 'group' , 'project']);
            $table->string('title')->nullable();
            $table->uuid('project_id')->nullable();
            $table->uuid('created_by');
            $table->timestamps();
            $table->timestamp('last_message_at')->nullable();

            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->index(['project_id', 'conversation_type']);
            $table->index('updated_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
