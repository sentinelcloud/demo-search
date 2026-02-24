<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReferrerStat extends Model
{
    protected $fillable = [
        'period', 'referrer_name', 'referrer', 'visitors', 'sessions',
    ];

    protected $casts = [
        'period' => 'datetime',
        'visitors' => 'integer',
        'sessions' => 'integer',
    ];
}
