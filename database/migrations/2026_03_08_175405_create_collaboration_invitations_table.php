<?php
// database/migrations/2024_01_01_000019_create_collaboration_invitations_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collaboration_invitations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('sender_id');
            $table->uuid('recipient_id');
            $table->uuid('project_id')->nullable();
            $table->enum('invitation_type', ['project_join', 'team_invite', 'collaboration_request', 'mentorship']);
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
    }

    public function down(): void
    {
        Schema::dropIfExists('collaboration_invitations');
    }
};
