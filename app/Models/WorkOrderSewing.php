<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkOrderSewing extends Model
{
    protected $fillable = [
        'work_order_id',
        'neck_overdeck_id',
        'underarm_overdeck_id',
        'side_split_id',
        'sewing_label_id',
        'detail_img_url',
        'notes',
    ];

    /**
     * Get the work order that owns the sewing
     */
    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    /**
     * Get the neck overdeck
     */
    public function neckOverdeck(): BelongsTo
    {
        return $this->belongsTo(NeckOverdeck::class);
    }

    /**
     * Get the underarm overdeck
     */
    public function underarmOverdeck(): BelongsTo
    {
        return $this->belongsTo(UnderarmOverdeck::class);
    }

    /**
     * Get the side split
     */
    public function sideSplit(): BelongsTo
    {
        return $this->belongsTo(SideSplit::class);
    }

    /**
     * Get the sewing label
     */
    public function sewingLabel(): BelongsTo
    {
        return $this->belongsTo(SewingLabel::class);
    }
}
