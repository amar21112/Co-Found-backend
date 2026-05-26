<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Drops all MySQL chat tables now that Firebase RTDB is the sole
 * source of truth for conversations and messages.
 *
 * video_calls.conversation_id is kept as a plain string column.
 * The FK to conversations is dropped since that table no longer exists.
 * The column carries the Firebase push key for reference and testing only.
 *
 *
 * Drop order respects FK dependencies:
 *   message_reactions, message_read_receipts
 *   shared_files (FK → files, messages, conversations)
 *   → messages
 *   → conversation_participants
 *   → conversations
 *   → files
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ── 0. SQLite: drop foreign key by recreating the table ───────────────
        if (DB::getDriverName() === 'sqlite') {
            // Disable foreign key checks temporarily
            DB::statement('PRAGMA foreign_keys = OFF');

            // Create new table without the FK
            Schema::create('video_calls_new', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('call_type');
                $table->string('conversation_id')->nullable();  // Plain string, no FK
                $table->uuid('project_id')->nullable();
                $table->uuid('initiated_by');
                $table->string('room_name');
                $table->string('room_url')->nullable();
                $table->timestamp('start_time')->nullable();
                $table->timestamp('end_time')->nullable();
                $table->integer('duration_seconds')->nullable();
                $table->string('status')->default('scheduled');
                $table->string('recording_url')->nullable();
                $table->timestamps();

                $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
                $table->foreign('initiated_by')->references('id')->on('users')->onDelete('cascade');
            });

            // Copy data
            DB::statement('INSERT INTO video_calls_new SELECT * FROM video_calls');

            // Swap tables
            Schema::drop('video_calls');
            Schema::rename('video_calls_new', 'video_calls');

            DB::statement('PRAGMA foreign_keys = ON');
        } else {
            // MySQL — drop the FK normally
            Schema::table('video_calls', function (Blueprint $table) {
                $table->dropForeign(['conversation_id']);
            });
        }

        // ── 1. Change video_calls.conversations_id to string ────────────────────────────
        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('video_calls', function (Blueprint $table) {
                $table->string('conversation_id')->nullable()->change();
            });
        }

        // ── 2. Drop leaf tables ───────────────────────────────────────────────
        Schema::dropIfExists('message_reactions');
        Schema::dropIfExists('message_read_receipts');
        Schema::dropIfExists('shared_files');

        // ── 3. Drop core chat tables ──────────────────────────────────────────
        Schema::dropIfExists('messages');
        Schema::dropIfExists('conversation_participants');
        Schema::dropIfExists('conversations');

        // ── 4. Drop file tables ───────────────────────────────────────────────
        Schema::dropIfExists('files');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('files', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('uploader_id');
            $table->string('file_name');
            $table->integer('file_size');
            $table->string('mime_type', 100);
            $table->string('storage_path', 500);
            $table->string('public_url', 500)->nullable();
            $table->string('thumbnail_url', 500)->nullable();
            $table->string('file_hash')->nullable();
            $table->boolean('upload_completed')->default(false);
            $table->timestamps();

            $table->foreign('uploader_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::create('conversations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->enum('conversation_type', ['direct', 'group', 'project']);
            $table->string('title')->nullable();
            $table->uuid('project_id')->nullable();
            $table->uuid('created_by');
            $table->timestamps();
            $table->timestamp('last_message_at')->nullable();

            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::create('conversation_participants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('conversation_id');
            $table->uuid('user_id');
            $table->timestamp('joined_at')->useCurrent();
            $table->timestamp('left_at')->nullable();
            $table->boolean('is_admin')->default(false);
            $table->boolean('muted')->default(false);
            $table->timestamp('muted_until')->nullable();

            $table->foreign('conversation_id')->references('id')->on('conversations')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['conversation_id', 'user_id']);
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('conversation_id');
            $table->uuid('sender_id');
            $table->enum('message_type', ['text', 'system', 'file', 'poll'])->default('text');
            $table->text('content');
            $table->json('formatted_content')->nullable();
            $table->uuid('replied_to_message_id')->nullable();
            $table->boolean('is_pinned')->default(false);
            $table->boolean('is_edited')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('conversation_id')->references('id')->on('conversations')->onDelete('cascade');
            $table->foreign('sender_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::create('message_read_receipts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('message_id');
            $table->uuid('user_id');
            $table->timestamp('read_at')->useCurrent();

            $table->foreign('message_id')->references('id')->on('messages')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['message_id', 'user_id']);
        });

        Schema::create('message_reactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('message_id');
            $table->uuid('user_id');
            $table->string('reaction', 50);
            $table->timestamps();

            $table->foreign('message_id')->references('id')->on('messages')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['message_id', 'user_id', 'reaction']);
        });

        Schema::create('shared_files', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('file_id');
            $table->uuid('conversation_id')->nullable();
            $table->uuid('message_id')->nullable();
            $table->uuid('shared_by');
            $table->enum('permission_level', ['view', 'download', 'edit'])->default('view');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->foreign('file_id')->references('id')->on('files')->onDelete('cascade');
            $table->foreign('conversation_id')->references('id')->on('conversations')->onDelete('cascade');
            $table->foreign('message_id')->references('id')->on('messages')->onDelete('cascade');
            $table->foreign('shared_by')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::table('video_calls', function (Blueprint $table) {
            $table->uuid('conversation_id')->nullable()->change();
            $table->foreign('conversation_id')->references('id')->on('conversations')->onDelete('cascade');
        });
    }
};
