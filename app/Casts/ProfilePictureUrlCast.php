<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * ProfilePictureUrlCast
 *
 * Converts the raw storage path stored in the database into a full
 * public URL on the way out, and passes the raw path through unchanged
 * on the way in.
 *
 * This cast is intentionally self-contained — pure string logic, no
 * service layer, no I/O, no container calls. That keeps the model
 * lightweight and this class trivially testable in isolation.
 *
 * Read  : "profile_pictures/uuid.jpg"        → "https://app.com/storage/profile_pictures/uuid.jpg"
 * Read  : "https://old-cdn.example.com/..."  → returned unchanged (legacy backward-compat)
 * Read  : null                               → null
 * Write : any value passed through as-is     (ProfileService owns storage)
 */
class ProfilePictureUrlCast implements CastsAttributes
{
    /**
     * Convert the stored path to a full public URL.
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if (! $value) {
            return null;
        }

        // Legacy rows from the old system may already contain a full URL.
        // Pass them through unchanged so those users are not broken.
        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            return $value;
        }

        // asset() resolves against APP_URL and works with the local public
        // driver without requiring Storage::url() on the filesystem instance.
        return asset('storage/' . $value);
    }

    /**
     * Pass the value through unchanged on write.
     *
     * ProfilePictureService::store() already returns the correct relative
     * path; this cast has no business transforming it further.
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return $value;
    }
}
