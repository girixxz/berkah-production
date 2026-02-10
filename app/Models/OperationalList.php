<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OperationalList extends Model
{
    protected $table = 'operational_lists';
    
    protected $fillable = [
        'category',
        'list_name',
        'sort_order',
    ];

    protected $casts = [
        'category' => 'string',
    ];
}
