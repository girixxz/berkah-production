<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkOrderCutting extends Model
{
    protected $fillable = [
        'work_order_id',
        'cutting_pattern_id',
        'chain_cloth_id',
        'rib_size_id',
        'custom_size_chart_img_url',
        'notes',
    ];

    /**
     * Get the work order that owns the cutting
     */
    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    /**
     * Get the cutting pattern
     */
    public function cuttingPattern(): BelongsTo
    {
        return $this->belongsTo(CuttingPattern::class);
    }

    /**
     * Get the chain cloth
     */
    public function chainCloth(): BelongsTo
    {
        return $this->belongsTo(ChainCloth::class);
    }

    /**
     * Get the rib size
     */
    public function ribSize(): BelongsTo
    {
        return $this->belongsTo(RibSize::class);
    }
}
