<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Session extends Model
{
    use HasUuids;

    protected $table = 'analytics_sessions';

    protected $fillable = [
        'visitor_id',
        'started_at',
        'ended_at',
        'duration_seconds',
        'is_bounce',
        'entry_path',
        'exit_path',
        'page_view_count',
        'referrer',
        'referrer_name',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_term',
        'utm_content',
        'browser',
        'browser_version',
        'os',
        'os_version',
        'device_type',
        'screen_width',
        'screen_height',
        'language',
        'country',
        'ip_hash',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'duration_seconds' => 'integer',
        'is_bounce' => 'boolean',
        'page_view_count' => 'integer',
        'screen_width' => 'integer',
        'screen_height' => 'integer',
    ];

    public function visitor(): BelongsTo
    {
        return $this->belongsTo(Visitor::class);
    }

    public function pageViews(): HasMany
    {
        return $this->hasMany(PageView::class);
    }

    public function analyticsEvents(): HasMany
    {
        return $this->hasMany(AnalyticsEvent::class);
    }
}
