<?php

namespace App\Models;

use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

/**
 * Extends Sanctum's PersonalAccessToken to handle UUID tokenable_id values.
 *
 * The personal_access_tokens table uses:
 *   - id            → bigInteger auto-increment (Sanctum's own PK — must stay integer)
 *   - tokenable_id  → char(36) UUID (our User model's PK)
 *
 * We must NOT change $keyType or $incrementing here — those affect the `id`
 * column which Sanctum uses to find token records via PersonalAccessToken::find($id).
 * Changing them to 'string'/false breaks that lookup and causes all authenticated
 * requests to return 401.
 *
 * The UUID compatibility is handled entirely by:
 *   1. uuidMorphs('tokenable') in the migration (tokenable_id as char(36))
 *   2. The $casts below which ensure tokenable_id is treated as a string
 *
 * Registered in AuthServiceProvider via Sanctum::usePersonalAccessTokenModel().
 */
class PersonalAccessToken extends SanctumPersonalAccessToken
{
    /**
     * Cast tokenable_id as string so Eloquent never coerces the UUID
     * to an integer when building morph queries (e.g. DELETE WHERE tokenable_id = ?).
     */
    protected $casts = [
        'abilities'    => 'json',
        'last_used_at' => 'datetime',
        'expires_at'   => 'datetime',
        'tokenable_id' => 'string',
    ];
}
