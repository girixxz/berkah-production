<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class OrderStage extends Model
{
    protected $fillable = [
        'order_id',
        'stage_id',
        'start_date',
        'deadline',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'deadline' => 'date',
    ];

    /**
     * Auto-update status based on start_date
     * If today >= start_date and status is pending, change to in_progress
     */
    public function getStatusAttribute($value)
    {
        // If status is pending and start_date exists and today >= start_date
        if ($value === 'pending' && $this->start_date && Carbon::today()->greaterThanOrEqualTo($this->start_date)) {
            return 'in_progress';
        }
        
        return $value;
    }

    /**
     * Get the order this stage belongs to
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /**
     * Get the production stage
     */
    public function productionStage(): BelongsTo
    {
        return $this->belongsTo(ProductionStage::class, 'stage_id');
    }
}
