<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceStat extends Model
{
    protected $fillable = [
        'period', 'browser', 'os', 'device_type', 'visitors', 'sessions',
    ];

    protected $casts = [
        'period' => 'datetime',
        'visitors' => 'integer',
        'sessions' => 'integer',
    ];
}
