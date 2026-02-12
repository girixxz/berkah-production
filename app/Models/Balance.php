<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\LoanCapital;
use App\Models\OperationalReport;

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

    /**
     * Get all loan capitals for this balance period.
     */
    public function loanCapitals(): HasMany
    {
        return $this->hasMany(LoanCapital::class);
    }

    /**
     * Get all operational reports for this balance period.
     */
    public function operationalReports(): HasMany
    {
        return $this->hasMany(OperationalReport::class);
    }
}
