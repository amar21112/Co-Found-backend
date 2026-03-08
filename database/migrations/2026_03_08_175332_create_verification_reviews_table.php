<?php
// database/migrations/2024_01_01_000005_create_verification_reviews_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verification_reviews', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('verification_id');
            $table->uuid('reviewer_id');
            $table->enum('review_action', ['approved', 'rejected', 'request_resubmission']);
            $table->text('review_notes')->nullable();
            $table->enum('rejection_reason_category', ['forgery', 'expired', 'unclear', 'mismatch', 'other'])->nullable();
            $table->timestamp('reviewed_at');
            $table->boolean('automated_checks_passed')->default(true);
            $table->json('automated_checks_data')->nullable();

            $table->foreign('verification_id')->references('id')->on('identity_verifications')->onDelete('cascade');
            $table->foreign('reviewer_id')->references('id')->on('users')->onDelete('cascade');
            $table->index('verification_id');
            $table->index('reviewer_id');
            $table->index('reviewed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verification_reviews');
    }
};
