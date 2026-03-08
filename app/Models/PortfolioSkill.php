<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PortfolioSkill extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'portfolio_skills';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'portfolio_item_id',
        'skill_name'
    ];

    public $timestamps = false;

    public function portfolioItem()
    {
        return $this->belongsTo(PortfolioItem::class, 'portfolio_item_id');
    }
}
