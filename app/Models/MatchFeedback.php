<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MatchFeedback extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'match_feedback';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'match_id',
        'user_id',
        'feedback_type'
    ];

    public function match()
    {
        return $this->belongsTo(MatchModel::class, 'match_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
