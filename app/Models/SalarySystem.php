<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalarySystem extends Model
{
    protected $fillable = [
        'type_name',
    ];

    public function employeeSalaries()
    {
        return $this->hasMany(EmployeeSalary::class);
    }
}
