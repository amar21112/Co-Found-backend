<?php
// database/migrations/2024_01_01_000036_create_content_moderation_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_moderation', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('moderator_id');
            $table->string('content_type', 50);
            $table->uuid('content_id');
            $table->enum('moderation_type', ['reported', 'auto_flagged', 'random_sampling', 'targeted']);
            $table->text('original_content')->nullable();
            $table->text('moderated_content')->nullable();
            $table->enum('action_taken', ['approved', 'edited', 'removed', 'quarantined', 'escalated']);
            $table->text('reason')->nullable();
            $table->string('guideline_referenced')->nullable();
            $table->timestamps();

            $table->foreign('moderator_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['content_type', 'content_id']);
            $table->index('moderator_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_moderation');
    }
};
