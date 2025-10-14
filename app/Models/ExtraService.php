<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExtraService extends Model
{
    protected $fillable = ['order_id', 'service_id', 'price'];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}
