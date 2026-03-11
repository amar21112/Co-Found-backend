<?php
// database/migrations/2024_01_01_000001_create_users_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('email')->unique();
            $table->string('username')->unique();
            $table->string('password');
            $table->string('full_name');
            $table->string('profile_picture_url', 500)->nullable();
            $table->text('bio')->nullable();
            $table->string('location')->nullable();
            $table->string('website_url', 500)->nullable();
            $table->string('linkedin_url', 500)->nullable();
            $table->string('github_url', 500)->nullable();
            $table->enum('role', ['guest', 'regular_user', 'moderator', 'administrator'])
                ->default('regular_user');
            $table->enum('account_status', ['pending', 'active', 'suspended', 'banned', 'deleted'])
                ->default('pending');
            $table->boolean('email_verified')->default(false);
            $table->boolean('identity_verified')->default(false);
            $table->enum('identity_verification_level', ['none', 'basic', 'advanced'])->nullable();
            $table->string('email_verification_token')->nullable();
            $table->timestamp('email_verification_expires')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->ipAddress('last_login_ip')->nullable();
            $table->integer('login_attempts')->default(0);
            $table->timestamp('locked_until')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['account_status', 'created_at']);
            $table->index('identity_verified');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
