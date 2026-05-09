<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // NOTE: SQLite is used only for testing — data loss is acceptable
        if (DB::getDriverName() === 'sqlite') {
            Schema::dropIfExists('reports');

            Schema::create('reports', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('reporter_id');
                $table->uuid('reported_user_id')->nullable();
                $table->string('reported_content_type', 50)->nullable();
                $table->uuid('reported_content_id')->nullable();
                $table->enum('report_type', ['harassment', 'spam', 'inappropriate', 'copyright', 'other']);
                $table->text('description')->nullable();
                $table->json('evidence')->nullable();
                $table->enum('status', ['pending', 'under_review', 'resolved', 'dismissed', 'escalated', 'withdrawn'])
                    ->default('pending');
                $table->enum('priority', ['critical', 'high', 'medium', 'low'])->default('medium');
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

        } else {
            // Production MySQL — safe ALTER, no data loss
            DB::statement("
                ALTER TABLE reports
                MODIFY COLUMN status ENUM(
                    'pending','under_review','resolved','dismissed','escalated','withdrawn'
                ) NOT NULL DEFAULT 'pending'
            ");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert: remove withdrawn (rows using it would need to be handled in real migrations)
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("
                ALTER TABLE reports
                MODIFY COLUMN status ENUM(
                    'pending','under_review','resolved','dismissed','escalated'
                ) NOT NULL DEFAULT 'pending'
            ");
        }
    }
};
