<?php
// database/migrations/2024_01_01_000035_create_reports_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('reporter_id');
            $table->uuid('reported_user_id')->nullable();
            $table->string('reported_content_type', 50)->nullable();
            $table->uuid('reported_content_id')->nullable();
            $table->enum('report_type', ['harassment', 'spam', 'inappropriate', 'copyright', 'other']);
            $table->text('description')->nullable();
            $table->json('evidence')->nullable();
            $table->enum('status', ['pending', 'under_review', 'resolved', 'dismissed', 'escalated'])
                ->default('pending');
            $table->enum('priority', ['high', 'medium', 'low'])->default('medium');
            $table->uuid('assigned_to')->nullable();
            $table->uuid('resolved_by')->nullable();
            $table->string('resolution_action')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->timestamps();
            $table->timestamp('resolved_at')->nullable();

            $table->foreign('reporter_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('reported_user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('assigned_to')->references('id')->on('users')->onDelete('set null');
            $table->foreign('resolved_by')->references('id')->on('users')->onDelete('set null');
            $table->index('reporter_id');
            $table->index('reported_user_id');
            $table->index('status');
            $table->index('priority');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
