<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkOrderPacking extends Model
{
    protected $fillable = [
        'work_order_id',
        'plastic_packing_id',
        'sticker_id',
        'hangtag_img_url',
        'notes',
    ];

    /**
     * Get the work order that owns the packing
     */
    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    /**
     * Get the plastic packing
     */
    public function plasticPacking(): BelongsTo
    {
        return $this->belongsTo(PlasticPacking::class);
    }

    /**
     * Get the sticker
     */
    public function sticker(): BelongsTo
    {
        return $this->belongsTo(Sticker::class);
    }
}
