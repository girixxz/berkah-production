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
     * Check if this report's period is locked (via report_periods table)
     */
    public function isLocked(): bool
    {
        $period = ReportPeriod::where('period_start', $this->period_start->toDateString())->first();
        return $period && $period->lock_status === 'locked';
    }

    /**
     * Get the material reports for this order
     */
    public function materialReports()
    {
        return $this->hasMany(OrderMaterialReport::class);
    }

    /**
     * Get the partner reports for this order
     */
    public function partnerReports()
    {
        return $this->hasMany(OrderPartnerReport::class);
    }
}
