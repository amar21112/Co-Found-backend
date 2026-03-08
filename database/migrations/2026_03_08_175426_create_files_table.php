<?php
// database/migrations/2024_01_01_000028_create_files_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
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
            $table->index('uploader_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('files');
    }
};
