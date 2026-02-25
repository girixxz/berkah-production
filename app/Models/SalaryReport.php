<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryReport extends Model
{
    protected $fillable = [
        'balance_id',
        'salary_date',
        'employee_salary_id',
        'payment_sequence',
        'amount',
        'notes',
        'payment_method',
        'proof_img',
        'report_status',
    ];

    protected $casts = [
        'salary_date' => 'date',
        'amount'      => 'decimal:2',
    ];

    /**
     * Get the balance that owns this salary report.
     */
    public function balance(): BelongsTo
    {
        return $this->belongsTo(Balance::class);
    }

    /**
     * Get the employee salary (pivot) for this report.
     */
    public function employeeSalary(): BelongsTo
    {
        return $this->belongsTo(EmployeeSalary::class);
    }
}
