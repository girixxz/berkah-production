<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaterialSupplier extends Model
{
    protected $fillable = [
        'supplier_name',
        'notes',
        'sort_order',
    ];
}
