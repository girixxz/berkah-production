<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InternalTransfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'transfer_date',
        'balance_id',
        'transfer_type',
        'amount',
        'notes',
        'proof_img',
    ];

    protected $casts = [
        'transfer_date' => 'date',
        'amount' => 'decimal:2',
    ];

    /**
     * Get the balance that owns the internal transfer.
     */
    public function balance()
    {
        return $this->belongsTo(Balance::class);
    }

    /**
     * Get formatted transfer type for display
     */
    public function getTransferTypeDisplayAttribute(): string
    {
        return match($this->transfer_type) {
            'transfer_to_cash' => 'Transfer → Cash',
            'cash_to_transfer' => 'Cash → Transfer',
            default => $this->transfer_type,
        };
    }

    /**
     * Get period string from balance relationship
     */
    public function getPeriodAttribute(): string
    {
        return $this->balance ? $this->balance->period_start->format('F Y') : '-';
    }
}
