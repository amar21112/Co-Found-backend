<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentModeration extends Model
{
    use HasUuids;

    public $timestamps    = false;
    protected $primaryKey = 'id';
    public $incrementing  = false;
    protected $keyType    = 'string';

    protected $fillable = [
        'moderator_id', 'content_type', 'content_id',
        'moderation_type', 'original_content', 'moderated_content',
        'action_taken', 'reason', 'guideline_referenced',
    ];

    protected $casts = ['created_at' => 'datetime'];

    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderator_id');
    }
}
