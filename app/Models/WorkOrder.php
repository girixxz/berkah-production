<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class WorkOrder extends Model
{
    protected $fillable = [
        'order_id',
        'design_variant_id',
        'mockup_img_url',
        'status',
    ];

    /**
     * Get the order that owns the work order
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the design variant for this work order
     */
    public function designVariant(): BelongsTo
    {
        return $this->belongsTo(DesignVariant::class);
    }

    /**
     * Get the cutting details for this work order
     */
    public function cutting(): HasOne
    {
        return $this->hasOne(WorkOrderCutting::class);
    }

    /**
     * Get the printing details for this work order
     */
    public function printing(): HasOne
    {
        return $this->hasOne(WorkOrderPrinting::class);
    }

    /**
     * Get the printing placement details for this work order
     */
    public function printingPlacement(): HasOne
    {
        return $this->hasOne(WorkOrderPrintingPlacement::class);
    }

    /**
     * Get the sewing details for this work order
     */
    public function sewing(): HasOne
    {
        return $this->hasOne(WorkOrderSewing::class);
    }

    /**
     * Get the packing details for this work order
     */
    public function packing(): HasOne
    {
        return $this->hasOne(WorkOrderPacking::class);
    }

    /**
     * Check if work order is complete (has all required details)
     */
    public function isComplete(): bool
    {
        return $this->cutting()->exists() &&
               $this->printing()->exists() &&
               $this->printingPlacement()->exists() &&
               $this->sewing()->exists() &&
               $this->packing()->exists();
    }
}
