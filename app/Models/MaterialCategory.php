<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaterialCategory extends Model
{
    protected $fillable = [
        'material_name',
    ];

    protected $appends = ['name'];

    /**
     * Accessor for name attribute (maps to material_name column)
     */
    public function getNameAttribute(): ?string
    {
        return $this->material_name;
    }

    /**
     * Get all orders using this material category
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'material_category_id');
    }
}
