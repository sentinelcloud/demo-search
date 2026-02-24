<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnalyticsReport extends Model
{
    protected $fillable = [
        'report_type', 'period_start', 'period_end', 'data',
        'generated_at', 'generation_time_ms', 'job_batch_id',
    ];

    protected $casts = [
        'period_start' => 'datetime',
        'period_end' => 'datetime',
        'generated_at' => 'datetime',
        'data' => 'array',
        'generation_time_ms' => 'integer',
    ];
}
