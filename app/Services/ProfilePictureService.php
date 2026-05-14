<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * ProfilePictureService
 *
 * Single Responsibility: handles storage, deletion, and public URL generation
 * for user profile pictures. Completely decoupled from User / Profile logic.
 *
 * Storage layout:
 *   disk  : public  (storage/app/public)
 *   path  : profile_pictures/{uuid}.{ext}
 *   url   : /storage/profile_pictures/{uuid}.{ext}  (requires: php artisan storage:link)
 *
 * Why asset() instead of Storage::disk('public')->url():
 *   The local filesystem driver used by the 'public' disk does not always expose
 *   a url() method depending on the Laravel version / config, which causes the
 *   "Undefined method 'url'" IDE / runtime error.
 *   asset('storage/' . $path) is the idiomatic Laravel way to build a public
 *   storage URL and works regardless of driver configuration.
 */
class ProfilePictureService
{
    private const DISK      = 'public';
    private const DIRECTORY = 'profile_pictures';

    // ─────────────────────────────────────────────────────────────────────────
    // Public API
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Store the uploaded file and return the relative storage path.
     *
     * The returned path (e.g. "profile_pictures/uuid.jpg") is what gets
     * persisted in the database column `profile_picture_url`.
     * The full public URL is derived on the way out via toUrl().
     *
     * @throws \RuntimeException  when the filesystem write fails.
     */
    public function store(UploadedFile $file): string
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $filename  = Str::uuid() . '.' . $extension;

        $path = $file->storeAs(
            self::DIRECTORY,
            $filename,
            self::DISK
        );

        if ($path === false) {
            throw new \RuntimeException('Failed to store the profile picture. Please try again.');
        }

        return $path;
    }

    /**
     * Delete the file that belongs to the given storage path.
     *
     * Silently ignores:
     *   - null / empty values
     *   - old-style external HTTP URLs left over from the previous system
     *   - paths that no longer exist on disk
     *
     * This makes the method idempotent and safe to call unconditionally.
     */
    public function delete(?string $storagePath): void
    {
        if (! $storagePath || ! $this->isStoredFile($storagePath)) {
            return;
        }

        // exists() + delete() are available on every driver
        if (Storage::disk(self::DISK)->exists($storagePath)) {
            Storage::disk(self::DISK)->delete($storagePath);
        }
    }

    /**
     * Convert a relative storage path to a full public URL.
     *
     * Uses asset() which resolves against APP_URL and is guaranteed to work
     * with the local public driver without requiring a url() method on the
     * filesystem instance.
     *
     * Examples:
     *   null                              → null
     *   "profile_pictures/uuid.jpg"       → "http://localhost/storage/profile_pictures/uuid.jpg"
     *   "https://old-cdn.example.com/..." → returned unchanged (backward-compat)
     *
     * @param  string|null $storagePath  Value stored in the database column.
     * @return string|null               Full public URL, or null when not set.
     */
    public function toUrl(?string $storagePath): ?string
    {
        if (! $storagePath) {
            return null;
        }

        // Old rows may still contain a full HTTP URL from the previous system.
        // Return them as-is so those users are not broken without a data migration.
        if ($this->isExternalUrl($storagePath)) {
            return $storagePath;
        }

        // asset('storage/profile_pictures/uuid.jpg')
        // → APP_URL . '/storage/profile_pictures/uuid.jpg'
        return asset('storage/' . $storagePath);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Private helpers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * True when the value is a relative path written by this service.
     * Distinguishes new stored paths from old HTTP URLs so we never
     * attempt to delete an external URL via Storage::delete().
     */
    private function isStoredFile(string $value): bool
    {
        return str_starts_with($value, self::DIRECTORY . '/');
    }

    /**
     * True when the value looks like an absolute HTTP/HTTPS URL.
     * Used to pass through legacy URLs unchanged in toUrl().
     */
    private function isExternalUrl(string $value): bool
    {
        return str_starts_with($value, 'http://') || str_starts_with($value, 'https://');
    }
}
