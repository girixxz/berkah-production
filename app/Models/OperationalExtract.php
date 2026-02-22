<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OperationalExtract extends Model
{
    protected $table = 'operational_extract_status';

    protected $fillable = [
        'period_start',
        'period_end',
        'is_extracted',
    ];

    protected $casts = [
        'period_start'  => 'date',
        'period_end'    => 'date',
        'is_extracted'  => 'boolean',
    ];
}
