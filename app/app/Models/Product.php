<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['published' => 'boolean', 'stock' => 'integer', 'unit_price' => 'integer', 'archived_at' => 'datetime', 'version' => 'integer'];
    }

    public function tiers(): HasMany
    {
        return $this->hasMany(PriceTier::class)->orderBy('minimum_quantity');
    }

    public function categoryRecord()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function scopePubliclyVisible($query)
    {
        return $query->where('published', true)->whereNull('archived_at');
    }

    public function getPhotoUrlAttribute(): string
    {
        return $this->photo_path ? route('product', $this->sku).'?foto=1' : asset('images/'.$this->image);
    }
}
