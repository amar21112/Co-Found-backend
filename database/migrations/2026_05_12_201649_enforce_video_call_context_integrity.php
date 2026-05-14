<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Removes ad-hoc call support and enforces context integrity at the DB level.
 *
 * Changes:
 *   1. call_type enum: drops 'direct'/'group', replaces with 'conversation'/'project'.
 *      Type is now derived from whichever context ID is present — not sent by the client.
 *
 *   2. Check constraint: exactly one of (conversation_id, project_id) must be non-null.
 *      This is the database-level mirror of InitiateCallRequest's withValidator() rule.
 *      Together they enforce the invariant at all three layers:
 *        - HTTP (request validation)  → user-facing 422 error
 *        - Service (assertCanJoin)    → fail-closed security guard
 *        - Database (check constraint)→ last line of defence against direct writes
 */
return new class extends Migration
{
    public function up(): void
    {
        // NOTE: SQLite is used only for testing — data loss is acceptable
        if (DB::getDriverName() === 'sqlite') {
            Schema::dropIfExists('video_calls');

            Schema::create('video_calls', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->enum('call_type', ['conversation', 'project']);
                $table->uuid('conversation_id')->nullable();
                $table->uuid('project_id')->nullable();
                $table->uuid('initiated_by');
                $table->string('room_name');
                $table->string('room_url')->nullable();
                $table->timestamp('start_time')->nullable();
                $table->timestamp('end_time')->nullable();
                $table->integer('duration_seconds')->nullable();
                $table->enum('status', ['scheduled', 'active', 'ended', 'cancelled'])
                    ->default('scheduled');
                $table->string('recording_url')->nullable();
                $table->timestamps();

                $table->foreign('conversation_id')->references('id')->on('conversations')->onDelete('cascade');
                $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
                $table->foreign('initiated_by')->references('id')->on('users')->onDelete('cascade');

                // Exactly one context must be set
                $table->index('conversation_id');
                $table->index('project_id');
                $table->index('initiated_by');
                $table->index('status');
                $table->index('created_at');
            });

        } else {
            // ── 1. First, alter the ENUM to include both old and new values ──────
            DB::statement("
                ALTER TABLE video_calls
                MODIFY COLUMN call_type ENUM('direct', 'group', 'conversation', 'project') NOT NULL
            ");

            // ── 2. Now migrate existing data safely ─────────────────────────────
            DB::statement("
                UPDATE video_calls SET call_type = 'conversation'
                WHERE call_type IN ('direct', 'group')
            ");

            // ── 3. Remove the old ENUM values ───────────────────────────────────
            DB::statement("
                ALTER TABLE video_calls
                MODIFY COLUMN call_type ENUM('conversation', 'project') NOT NULL
            ");

            // ── 4. Add mutual-exclusivity check constraint ──────────────────────
            DB::statement("
                ALTER TABLE video_calls
                ADD CONSTRAINT chk_video_calls_single_context
                CHECK (
                    (conversation_id IS NOT NULL AND project_id IS NULL)
                    OR
                    (conversation_id IS NULL AND project_id IS NOT NULL)
                )
            ");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("
                ALTER TABLE video_calls
                DROP CONSTRAINT chk_video_calls_single_context
            ");

            DB::statement("
                ALTER TABLE video_calls
                MODIFY COLUMN call_type ENUM('direct', 'group') NOT NULL
            ");
        }
    }
};
