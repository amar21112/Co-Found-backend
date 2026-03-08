<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PortfolioItem extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'portfolio_items';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'file_url',
        'thumbnail_url',
        'item_type',
        'external_url',
        'visibility',
        'is_featured'
    ];

    protected $casts = [
        'is_featured' => 'boolean'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function skills()
    {
        return $this->hasMany(PortfolioSkill::class, 'portfolio_item_id');
    }

    public function scopePublic($query)
    {
        return $query->where('visibility', 'public');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('item_type', $type);
    }

    public function isVisibleTo($user)
    {
        if ($this->visibility === 'public') {
            return true;
        }

        if ($this->visibility === 'connections' && $user) {
            return $this->user->connections()
                ->where('recipient_id', $user->id)
                ->where('status', 'accepted')
                ->exists();
        }

        return $this->visibility === 'private' && $user && $user->id === $this->user_id;
    }
}
