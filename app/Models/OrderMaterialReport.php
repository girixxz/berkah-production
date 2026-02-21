<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderMaterialReport extends Model
{
    protected $fillable = [
        'balance_id',
        'order_report_id',
        'purchase_date',
        'purchase_type',
        'material_name',
        'material_supplier_id',
        'amount',
        'notes',
        'payment_method',
        'proof_img',
        'proof_img2',
        'report_status',
    ];

    protected $casts = [
        'purchase_date' => 'date',
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

    public function materialSupplier()
    {
        return $this->belongsTo(MaterialSupplier::class);
    }
}
