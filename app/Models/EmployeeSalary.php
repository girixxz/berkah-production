<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeSalary extends Model
{
    protected $fillable = [
        'user_id',
        'salary_system_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function salarySystem()
    {
        return $this->belongsTo(SalarySystem::class);
    }
}
