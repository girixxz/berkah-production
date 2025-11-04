<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductCategory extends Model
{
    protected $fillable = [
        'product_name',
    ];

    protected $appends = ['name'];

    /**
     * Accessor for name attribute (maps to product_name column)
     */
    public function getNameAttribute(): ?string
    {
        return $this->product_name;
    }

    /**
     * Get all orders for this product category
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'product_category_id');
    }
}
