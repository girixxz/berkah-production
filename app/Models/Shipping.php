<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shipping extends Model
{
    protected $fillable = ['shipping_name'];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
