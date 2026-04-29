<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds a SHA-256 hash of the id_card_number for uniqueness enforcement.
 *
 * WHY A SEPARATE HASH COLUMN:
 * id_card_number is stored encrypted using Laravel's encrypt() which produces
 * a different ciphertext each call — it cannot be used for DB-level uniqueness.
 * We store a deterministic HMAC-SHA256 hash (keyed with APP_KEY) alongside it.
 * The hash is used ONLY for duplicate detection. The encrypted value is used
 * for display/audit. Neither the raw number nor the hash is ever returned to
 * the client.
 *
 * The unique index covers only non-null values — users who don't provide a card
 * number are not affected.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('identity_verifications', function (Blueprint $table) {
            // SHA-256 hex digest = 64 chars
            $table->string('id_card_number_hash', 64)
                ->nullable()
                ->after('id_card_number')
                ->comment('HMAC-SHA256 of raw id_card_number for duplicate detection. Never returned to client.');

            $table->unique('id_card_number_hash', 'idx_unique_id_card_hash');
        });
    }

    public function down(): void
    {
        Schema::table('identity_verifications', function (Blueprint $table) {
            $table->dropUnique('idx_unique_id_card_hash');
            $table->dropColumn('id_card_number_hash');
        });
    }
};
