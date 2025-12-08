<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    protected $fillable = [
        'service_name',
        'sort_order',
    ];

    /**
     * Get all extra services using this service
     */
    public function extraServices(): HasMany
    {
        return $this->hasMany(ExtraService::class, 'service_id');
    }
}
