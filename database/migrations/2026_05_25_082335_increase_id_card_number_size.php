<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            // NOTE: SQLite is used only for testing — data loss is acceptable
            Schema::dropIfExists('identity_verifications');

            Schema::create('identity_verifications', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('user_id')->unique();
                $table->string('id_card_image_front', 500);
                $table->string('id_card_image_back', 500);
                $table->text('id_card_number')->nullable(); // Encrypted
                $table->string('id_card_number_hash', 64)
                    ->nullable()
                    ->comment('HMAC-SHA256 of raw id_card_number for duplicate detection. Never returned to client.');
                $table->string('full_name_on_card');
                $table->date('date_of_birth');
                $table->string('nationality', 100)->nullable();
                $table->date('expiry_date')->nullable();
                $table->enum('submission_method', ['mobile_capture', 'webcam']);
                $table->ipAddress('ip_address')->nullable();
                $table->text('user_agent')->nullable();
                $table->text('device_info')->nullable();
                $table->boolean('liveness_check_passed')->default(false);
                $table->json('liveness_check_data')->nullable();
                $table->decimal('face_match_score', 3, 2)->nullable();
                $table->enum('verification_status', ['pending', 'under_review', 'verified', 'rejected', 'escalated'])
                    ->default('pending');
                $table->text('rejection_reason')->nullable();
                $table->timestamps();

                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->index('verification_status');
                $table->unique('id_card_number_hash', 'idx_unique_id_card_hash');
            });
        } else {
            // Production MySQL — safe ALTER, no data loss
            Schema::table('identity_verifications', function (Blueprint $table) {
                $table->text('id_card_number')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('identity_verifications', function (Blueprint $table) {
                $table->string('id_card_number')->nullable()->change();
            });
        }
    }
};
