<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderReport extends Model
{
    protected $fillable = [
        'period_start',
        'period_end',
        'order_id',
        'invoice_id',
        'product_type',
        'lock_status',
        'note',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
    ];

    /**
     * Get the order that owns the report
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /**
     * Get the invoice that owns the report
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    /**
     * Check if report is locked
     */
    public function isLocked(): bool
    {
        return $this->lock_status === 'locked';
    }

    /**
     * Check if report is draft
     */
    public function isDraft(): bool
    {
        return $this->lock_status === 'draft';
    }

    /**
     * Lock the report
     */
    public function lock(): bool
    {
        return $this->update(['lock_status' => 'locked']);
    }

    /**
     * Unlock the report (set to draft)
     */
    public function unlock(): bool
    {
        return $this->update(['lock_status' => 'draft']);
    }

    /**
     * Get the material reports for this order
     */
    public function materialReports()
    {
        return $this->hasMany(OrderMaterialReport::class);
    }
}
