<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class File extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'files';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'uploader_id',
        'file_name',
        'file_size',
        'mime_type',
        'storage_path',
        'public_url',
        'thumbnail_url',
        'file_hash',
        'upload_completed'
    ];

    protected $casts = [
        'file_size' => 'integer',
        'upload_completed' => 'boolean'
    ];

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploader_id');
    }

    public function shares()
    {
        return $this->hasMany(SharedFile::class, 'file_id');
    }

    public function scopeCompleted($query)
    {
        return $query->where('upload_completed', true);
    }

    public function scopeByMimeType($query, $mimeType)
    {
        return $query->where('mime_type', 'LIKE', $mimeType . '%');
    }

    public function scopeImages($query)
    {
        return $query->where('mime_type', 'LIKE', 'image/%');
    }

    public function scopeDocuments($query)
    {
        return $query->where('mime_type', 'LIKE', 'application/%');
    }

    public function getFileSizeFormattedAttribute()
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function getExtensionAttribute()
    {
        return pathinfo($this->file_name, PATHINFO_EXTENSION);
    }
}
