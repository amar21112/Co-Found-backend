<?php
// database/migrations/2024_01_01_000016_create_project_applications_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_applications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('project_id');
            $table->uuid('applicant_id');
            $table->uuid('role_id')->nullable();
            $table->text('cover_message')->nullable();
            $table->string('proposed_role', 100)->nullable();
            $table->string('availability', 50)->nullable();
            $table->enum('status', ['pending', 'reviewing', 'accepted', 'rejected', 'withdrawn', 'expired'])
                ->default('pending');
            $table->decimal('match_score', 3, 2)->nullable();
            $table->uuid('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('applied_at')->useCurrent();
            $table->timestamps();

            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
            $table->foreign('applicant_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('role_id')->references('id')->on('project_roles')->onDelete('set null');
            $table->foreign('reviewed_by')->references('id')->on('users')->onDelete('set null');
            $table->unique(['project_id', 'applicant_id']);
            $table->index(['project_id', 'status']);
            $table->index('applicant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_applications');
    }
};
