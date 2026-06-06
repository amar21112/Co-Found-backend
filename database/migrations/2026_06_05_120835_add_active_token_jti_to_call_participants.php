<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('call_participants', function (Blueprint $table) {
            // UUID of the JWT issued to this participant on their last /join.
            // Nullable: null for participants created before this migration,
            // and for the initial host row created by initiate() before any
            // /join call (the host gets their jti when they call /join).
            $table->uuid('active_token_jti')->nullable()->after('role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('call_participants', function (Blueprint $table) {
            $table->dropColumn('active_token_jti');
        });
    }
};
