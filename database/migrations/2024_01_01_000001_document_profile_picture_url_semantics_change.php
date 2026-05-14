<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * No structural change is required to the `users` table.
 *
 * The existing `profile_picture_url varchar(500)` column is wide enough to
 * hold a relative storage path such as:
 *   "profile_pictures/550e8400-e29b-41d4-a716-446655440000.jpg"  (≈ 60 chars)
 *
 * This migration exists purely as an audit record to document that the column
 * semantics changed from "external URL string" to "relative storage path".
 *
 * If you are running a fresh install this migration does nothing.
 * If you have existing rows with old external URLs they will continue to work
 * because ProfilePictureService::toUrl() detects non-path values and returns
 * them unchanged (see isStoredFile() guard).
 *
 * Run: php artisan migrate
 */
return new class extends Migration
{
    public function up(): void
    {
        // Column already exists with a compatible type — no DDL change needed.
        // Intentionally left empty.
    }

    public function down(): void
    {
        // Nothing to reverse.
    }
};
