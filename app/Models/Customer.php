<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $fillable = [
        'customer_name',
        'phone',
        'province_id',
        'city_id',
        'district_id',
        'village_id',
        'address',
    ];

    /**
     * Get all orders for this customer
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'customer_id');
    }

    /**
     * Get location names from API (lazy loading)
     * Note: province_id, city_id, district_id, village_id are string IDs from emsifa API
     */
    // No direct relationships - location data fetched from API when needed
}
