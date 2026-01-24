<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    protected $fillable = [
        'order_id',
        'invoice_no',
        'total_bill',
        'amount_paid',
        'amount_due',
        'status',
        'notes',
    ];

    protected $casts = [
        'total_bill' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'amount_due' => 'decimal:2',
    ];

    /**
     * Get the order this invoice belongs to
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /**
     * Get all payments for this invoice
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'invoice_id');
    }

    /**
     * Get all order reports for this invoice
     */
    public function orderReports(): HasMany
    {
        return $this->hasMany(OrderReport::class, 'invoice_id');
    }
}
