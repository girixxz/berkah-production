<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SewingLabel extends Model
{
    use HasFactory;

    protected $fillable = ['name'];
}
