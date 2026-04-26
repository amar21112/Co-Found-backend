<?php

namespace App\Traits;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

trait FileUploadTrait
{
    /**
     * Upload a file to local storage.
     *
     * @param UploadedFile $file The file to upload
     * @param string $directory The directory to store the file
     * @param string $disk The storage disk to use
     * @param string|null $filename Custom filename, null generated UUID
     * @return string|false The path to the stored file or false on failure
     */
    public function uploadFile(UploadedFile $file, string $directory = 'uploads', string $disk = 'public', string $filename = null)
    {
        $filename = $filename ?? (Str::uuid() . '.' . $file->getClientOriginalExtension());
        
        return $file->storeAs($directory, $filename, ['disk' => $disk]);
    }

    /**
     * Delete a file from local storage.
     *
     * @param string $path The path of the file to delete
     * @param string $disk The storage disk
     * @return bool True on success
     */
    public function deleteFile(string $path, string $disk = 'public'): bool
    {
        if (Storage::disk($disk)->exists($path)) {
            return Storage::disk($disk)->delete($path);
        }

        return false;
    }

    /**
     * Get the URL for a stored file.
     *
     * @param string $path The path of the file
     * @param string $disk The storage disk
     * @return string The file URL
     */
    public function getFileUrl(string $path, string $disk = 'public'): string
    {
        return Storage::disk($disk)->url($path);
    }
}
