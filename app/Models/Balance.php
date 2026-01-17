<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Balance extends Model
{
    protected $fillable = [
        'period_start',
        'period_end',
        'total_balance',
        'transfer_balance',
        'cash_balance',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'total_balance' => 'decimal:2',
        'transfer_balance' => 'decimal:2',
        'cash_balance' => 'decimal:2',
    ];
}
