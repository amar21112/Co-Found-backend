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
            Schema::table('collaboration_invitations', function () {
                Schema::dropIfExists('collaboration_invitations');

                Schema::create('collaboration_invitations', function (Blueprint $table) {
                    $table->uuid('id')->primary();
                    $table->uuid('sender_id');
                    $table->uuid('recipient_id');
                    $table->uuid('project_id')->nullable();
                    $table->enum('invitation_type', ['project_join', 'team_invite', 'collaboration_request', 'mentorship', 'co_founder']);
                    $table->string('role', 100)->nullable();
                    $table->text('message')->nullable();
                    $table->enum('status', ['pending', 'accepted', 'declined', 'expired', 'withdrawn'])
                        ->default('pending');
                    $table->timestamp('expires_at')->nullable();
                    $table->timestamp('responded_at')->nullable();
                    $table->text('response_message')->nullable();
                    $table->timestamps();

                    $table->foreign('sender_id')->references('id')->on('users')->onDelete('cascade');
                    $table->foreign('recipient_id')->references('id')->on('users')->onDelete('cascade');
                    $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
                    $table->index(['sender_id', 'recipient_id', 'status']);
                    $table->index('recipient_id');
                    $table->index('project_id');
                });

            });
        } else {
            // Production MySQL — safe ALTER, no data loss

            DB::statement("
                ALTER TABLE collaboration_invitations
                MODIFY COLUMN invitation_type ENUM(
                    'project_join','team_invite','collaboration_request','mentorship','co_founder'
                ) NOT NULL
            ");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert: remove co_founder (rows using it would need to be handled in real migrations)

        DB::statement("
                ALTER TABLE collaboration_invitations
                MODIFY COLUMN invitation_type ENUM(
                    'project_join','team_invite','collaboration_request','mentorship'
                ) NOT NULL
        ");
    }
};
