<?php
// database/migrations/2024_01_01_000030_create_video_calls_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('video_calls', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->enum('call_type', ['direct', 'group']);
            $table->uuid('conversation_id')->nullable();
            $table->uuid('project_id')->nullable();
            $table->uuid('initiated_by');
            $table->string('room_name')->unique();
            $table->string('room_url', 500)->nullable();
            $table->timestamp('start_time')->nullable();
            $table->timestamp('end_time')->nullable();
            $table->integer('duration_seconds')->nullable();
            $table->enum('status', ['scheduled', 'active', 'ended', 'cancelled'])->default('scheduled');
            $table->string('recording_url', 500)->nullable();
            $table->timestamps();

            $table->foreign('conversation_id')->references('id')->on('conversations')->onDelete('cascade');
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
            $table->foreign('initiated_by')->references('id')->on('users')->onDelete('cascade');
            $table->index('conversation_id');
            $table->index('project_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_calls');
    }
};
