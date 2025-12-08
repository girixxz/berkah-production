<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NeckOverdeck extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'sort_order'];
}
