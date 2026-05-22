<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $event_type
 * @property string|null $user_id
 * @property string|null $session_id
 * @property array|null $properties
 * @property string|null $page_url
 * @property string|null $referrer_url
 * @property string|null $user_agent
 * @property string|null $ip_address
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @method static Builder|AnalyticsEvent create(array $attributes = [])
 * @method static Builder|AnalyticsEvent find($id, $columns = ['*'])
 * @method static Builder|AnalyticsEvent findOrFail($id, $columns = ['*'])
 * @method static Builder|AnalyticsEvent first($columns = ['*'])
 * @method static Builder|AnalyticsEvent firstOrCreate(array $attributes = [], array $values = [])
 * @method static Builder|AnalyticsEvent firstOrFail($columns = ['*'])
 * @method static Builder|AnalyticsEvent newModelQuery()
 * @method static Builder|AnalyticsEvent newQuery()
 * @method static Builder|AnalyticsEvent query()
 * @method static Builder|AnalyticsEvent where($column, $operatorOrValue = null, $value = null, $boolean = 'and')
 * @method static Builder|AnalyticsEvent whereIn($column, $values, $boolean = 'and', $not = false)
 * @method static Builder|AnalyticsEvent with($relations, $callback = null)
 *
 * @method static Builder|AnalyticsEvent whereId($value)
 * @method static Builder|AnalyticsEvent whereEventType($value)
 * @method static Builder|AnalyticsEvent whereUserId($value)
 * @method static Builder|AnalyticsEvent whereSessionId($value)
 * @method static Builder|AnalyticsEvent whereProperties($value)
 * @method static Builder|AnalyticsEvent wherePageUrl($value)
 * @method static Builder|AnalyticsEvent whereReferrerUrl($value)
 * @method static Builder|AnalyticsEvent whereUserAgent($value)
 * @method static Builder|AnalyticsEvent whereIpAddress($value)
 * @method static Builder|AnalyticsEvent whereCreatedAt($value)
 * @method static Builder|AnalyticsEvent whereUpdatedAt($value)
 *
 * @property-read User $user
 */
class AnalyticsEvent extends Model
{
    use HasUuids;

    protected $primaryKey = 'id';
    public $incrementing  = false;
    protected $keyType    = 'string';

    protected $fillable = [
        'event_type', 'user_id', 'session_id', 'properties',
        'page_url', 'referrer_url', 'user_agent', 'ip_address',
    ];

    protected $casts = [
        'properties' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
