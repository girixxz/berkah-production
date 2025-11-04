<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaterialSize extends Model
{
    protected $fillable = [
        'size_name',
        'extra_price',
    ];

    protected $casts = [
        'extra_price' => 'decimal:2',
    ];

    protected $appends = ['name'];

    /**
     * Accessor for name attribute (maps to size_name column)
     */
    public function getNameAttribute(): ?string
    {
        return $this->size_name;
    }

    /**
     * Get all order items using this size
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'size_id');
    }
}
