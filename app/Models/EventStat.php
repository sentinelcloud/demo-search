<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventStat extends Model
{
    protected $fillable = [
        'period', 'event_name', 'product_id', 'count', 'unique_visitors',
    ];

    protected $casts = [
        'period' => 'datetime',
        'count' => 'integer',
        'unique_visitors' => 'integer',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
