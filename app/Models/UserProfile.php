<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserProfile extends Model
{
    protected $fillable = [
        'user_id',
        'fullname',
        'phone_number',
        'gender',
        'birth_date',
        'work_date',
        'dress_size',
        'address',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
