<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContentModeration extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'content_moderation';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'moderator_id',
        'content_type',
        'content_id',
        'moderation_type',
        'original_content',
        'moderated_content',
        'action_taken',
        'reason',
        'guideline_referenced'
    ];

    public function moderator()
    {
        return $this->belongsTo(User::class, 'moderator_id');
    }

    public function content()
    {
        return $this->morphTo('content', 'content_type', 'content_id');
    }

    public function scopeByModerator($query, $moderatorId)
    {
        return $query->where('moderator_id', $moderatorId);
    }

    public function scopeByContentType($query, $contentType)
    {
        return $query->where('content_type', $contentType);
    }

    public function scopeForContent($query, $contentType, $contentId)
    {
        return $query->where('content_type', $contentType)
            ->where('content_id', $contentId);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }
}
