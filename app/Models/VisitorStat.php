<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VisitorStat extends Model
{
    protected $fillable = [
        'period', 'visitors', 'new_visitors', 'sessions',
        'page_views', 'bounces', 'avg_duration_seconds',
    ];

    protected $casts = [
        'period' => 'datetime',
        'visitors' => 'integer',
        'new_visitors' => 'integer',
        'sessions' => 'integer',
        'page_views' => 'integer',
        'bounces' => 'integer',
        'avg_duration_seconds' => 'float',
    ];
}
