<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportPeriod extends Model
{
    use HasFactory;

    protected $fillable = [
        'period_start',
        'period_end',
        'lock_status',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
    ];

    /**
     * Check if this period is locked
     */
    public function isLocked(): bool
    {
        return $this->lock_status === 'locked';
    }

    /**
     * Check if this period is unlocked (not locked)
     */
    public function isDraft(): bool
    {
        return $this->lock_status === 'unlocked';
    }
}
