<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderPartnerReport extends Model
{
    protected $fillable = [
        'balance_id',
        'order_report_id',
        'service_date',
        'service_type',
        'service_name',
        'support_partner_id',
        'amount',
        'notes',
        'payment_method',
        'proof_img',
    ];

    protected $casts = [
        'service_date' => 'date',
        'amount' => 'decimal:2',
    ];

    // Relationships
    public function balance()
    {
        return $this->belongsTo(Balance::class);
    }

    public function orderReport()
    {
        return $this->belongsTo(OrderReport::class);
    }

    public function supportPartner()
    {
        return $this->belongsTo(SupportPartner::class);
    }
}
