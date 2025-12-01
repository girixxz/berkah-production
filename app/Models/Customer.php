<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
     * Get location names from external API
     * Note: province_id, city_id, district_id, village_id are stored as strings from external API
     * Location data is fetched from API when needed, not from local database
     */
}
