<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class File extends Model
{
    use HasUuids;

    public $timestamps    = false;
    protected $primaryKey = 'id';
    public $incrementing  = false;
    protected $keyType    = 'string';

    protected $fillable = [
        'uploader_id', 'file_name', 'file_size', 'mime_type',
        'storage_path', 'public_url', 'thumbnail_url',
        'file_hash', 'upload_completed',
    ];

    protected $casts = [
        'file_size'        => 'integer',
        'upload_completed' => 'boolean',
        'created_at'       => 'datetime',
    ];

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploader_id');
    }

    public function shares(): HasMany
    {
        return $this->hasMany(SharedFile::class);
    }
}
