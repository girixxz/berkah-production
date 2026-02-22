<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Balance;

class OperationalReport extends Model
{
    protected $table = 'operational_reports';

    protected $fillable = [
        'balance_id',
        'operational_date',
        'operational_type',
        'category',
        'operational_name',
        'amount',
        'notes',
        'payment_method',
        'proof_img',
        'proof_img2',
        'report_status',
    ];

    protected $casts = [
        'operational_date' => 'date',
        'amount' => 'decimal:2',
    ];

    /**
     * Get the balance that owns this operational report.
     */
    public function balance()
    {
        return $this->belongsTo(Balance::class);
    }
}
