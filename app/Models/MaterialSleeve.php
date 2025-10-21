<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaterialSleeve extends Model
{
    protected $fillable = [
        'sleeve_name',
    ];

    /**
     * Get all order items using this sleeve type
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'sleeve_id');
    }
}
