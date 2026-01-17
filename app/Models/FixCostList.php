<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FixCostList extends Model
{
    protected $fillable = [
        'category',
        'list_name',
        'sort_order',
    ];

    protected $casts = [
        'category' => 'string',
    ];
}
