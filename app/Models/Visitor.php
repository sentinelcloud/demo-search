<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Visitor extends Model
{
    protected $fillable = [
        'fingerprint',
        'first_seen_at',
        'last_seen_at',
        'total_sessions',
        'total_page_views',
    ];

    protected $casts = [
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'total_sessions' => 'integer',
        'total_page_views' => 'integer',
    ];

    public function sessions(): HasMany
    {
        return $this->hasMany(Session::class);
    }

    public function pageViews(): HasMany
    {
        return $this->hasMany(PageView::class);
    }

    public function analyticsEvents(): HasMany
    {
        return $this->hasMany(AnalyticsEvent::class);
    }

    public function scopeActiveToday($query)
    {
        return $query->where('last_seen_at', '>=', now()->startOfDay());
    }

    public function scopeNewSince($query, $date)
    {
        return $query->where('first_seen_at', '>=', $date);
    }
}
