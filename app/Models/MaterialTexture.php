<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaterialTexture extends Model
{
    protected $fillable = [
        'texture_name',
        'sort_order',
    ];

    protected $appends = ['name'];

    /**
     * Accessor for name attribute (maps to texture_name column)
     */
    public function getNameAttribute(): ?string
    {
        return $this->texture_name;
    }

    /**
     * Get all orders using this material texture
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'material_texture_id');
    }
}
