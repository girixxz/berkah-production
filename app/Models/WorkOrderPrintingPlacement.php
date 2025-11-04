<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkOrderPrintingPlacement extends Model
{
    protected $fillable = [
        'work_order_id',
        'detail_img_url',
        'notes',
    ];

    /**
     * Get the work order that owns the printing placement
     */
    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }
}
