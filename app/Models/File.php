<?php

namespace App\Models;

use Database\Factories\FileFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * @property string $id
 * @property string $uploader_id
 * @property string $file_name
 * @property int $file_size
 * @property string $mime_type
 * @property string $storage_path
 * @property string|null $public_url
 * @property string|null $thumbnail_url
 * @property string|null $file_hash
 * @property bool $upload_completed
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @method static Builder|File create(array $attributes = [])
 * @method static Builder|File find($id, $columns = ['*'])
 * @method static Builder|File findOrFail($id, $columns = ['*'])
 * @method static Builder|File first($columns = ['*'])
 * @method static Builder|File firstOrCreate(array $attributes = [], array $values = [])
 * @method static Builder|File firstOrFail($columns = ['*'])
 * @method static Builder|File newModelQuery()
 * @method static Builder|File newQuery()
 * @method static Builder|File query()
 * @method static Builder|File where($column, $operatorOrValue = null, $value = null, $boolean = 'and')
 * @method static Builder|File whereIn($column, $values, $boolean = 'and', $not = false)
 * @method static Builder|File with($relations, $callback = null)
 *
 * @method static Builder|File whereId($value)
 * @method static Builder|File whereUploaderId($value)
 * @method static Builder|File whereFileName($value)
 * @method static Builder|File whereFileSize($value)
 * @method static Builder|File whereMimeType($value)
 * @method static Builder|File whereStoragePath($value)
 * @method static Builder|File wherePublicUrl($value)
 * @method static Builder|File whereThumbnailUrl($value)
 * @method static Builder|File whereFileHash($value)
 * @method static Builder|File whereUploadCompleted($value)
 * @method static Builder|File whereCreatedAt($value)
 * @method static Builder|File whereUpdatedAt($value)
 *
 * @property-read User $uploader
 * @property-read Collection|SharedFile[] $shares
 *
 * @method static FileFactory factory($count = null, $state = [])
 */
class File extends Model
{
    use HasUuids, HasFactory;

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
