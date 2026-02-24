<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Scout\Searchable;

class Product extends Model
{
    use HasFactory, Searchable;

    protected $fillable = [
        'title',
        'description',
        'category',
        'brand',
        'tags',
        'price',
        'rating',
        'in_stock',
        'image_url',
    ];

    protected $casts = [
        'tags' => 'array',
        'price' => 'float',
        'rating' => 'float',
        'in_stock' => 'boolean',
    ];

    public function analyticsEvents(): HasMany
    {
        return $this->hasMany(AnalyticsEvent::class);
    }

    /**
     * Get the indexable data array for the model.
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => (int) $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'category' => $this->category,
            'brand' => $this->brand,
            'tags' => $this->tags,
            'price' => (float) $this->price,
            'rating' => (float) $this->rating,
            'in_stock' => (bool) $this->in_stock,
            'image_url' => $this->image_url,
            'created_at' => $this->created_at?->timestamp,
        ];
    }

    /**
     * Get the name of the index associated with the model.
     */
    public function searchableAs(): string
    {
        return 'products';
    }
}
