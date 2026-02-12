<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Balance;
use App\Models\LoanRepayment;

class LoanCapital extends Model
{
    protected $fillable = [
        'balance_id',
        'loan_date',
        'amount',
        'remaining_amount',
        'payment_method',
        'proof_img',
        'status',
        'notes',
    ];

    protected $casts = [
        'loan_date' => 'date',
        'amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
    ];

    /**
     * Get the balance period for this loan.
     */
    public function balance(): BelongsTo
    {
        return $this->belongsTo(Balance::class);
    }

    /**
     * Get the repayments for the loan.
     */
    public function repayments(): HasMany
    {
        return $this->hasMany(LoanRepayment::class, 'loan_id');
    }
}
