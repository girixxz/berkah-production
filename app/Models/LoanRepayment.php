<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanRepayment extends Model
{
    protected $fillable = [
        'loan_id',
        'paid_date',
        'amount',
        'payment_method',
        'proof_img',
        'notes',
    ];

    protected $casts = [
        'paid_date' => 'date',
        'amount' => 'decimal:2',
    ];

    /**
     * Get the loan that owns the repayment.
     */
    public function loanCapital(): BelongsTo
    {
        return $this->belongsTo(LoanCapital::class, 'loan_id');
    }
}
