<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkOrderPrinting extends Model
{
    protected $fillable = [
        'work_order_id',
        'print_ink_id',
        'finishing_id',
        'detail_img_url',
        'notes',
    ];

    /**
     * Get the work order that owns the printing
     */
    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    /**
     * Get the print ink
     */
    public function printInk(): BelongsTo
    {
        return $this->belongsTo(PrintInk::class);
    }

    /**
     * Get the finishing
     */
    public function finishing(): BelongsTo
    {
        return $this->belongsTo(Finishing::class);
    }
}
