<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PageStat extends Model
{
    protected $fillable = [
        'period', 'path', 'visitors', 'page_views',
        'entries', 'exits', 'bounces', 'avg_duration_ms',
    ];

    protected $casts = [
        'period' => 'datetime',
        'visitors' => 'integer',
        'page_views' => 'integer',
        'entries' => 'integer',
        'exits' => 'integer',
        'bounces' => 'integer',
        'avg_duration_ms' => 'float',
    ];
}
