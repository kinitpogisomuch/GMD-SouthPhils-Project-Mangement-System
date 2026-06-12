<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalaryRecord extends Model
{
    protected $fillable = [
        'employee_id',
        'pay_period',       // week-start date "2026-06-02" (Monday)
        'daily_rate',
        'days_worked',
        'gross_pay',
        'total_deductions',
        'net_pay',
        'notes',
    ];

    protected $casts = [
        'daily_rate'        => 'decimal:2',
        'days_worked'       => 'decimal:2',
        'gross_pay'         => 'decimal:2',
        'total_deductions'  => 'decimal:2',
        'net_pay'           => 'decimal:2',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public static function compute(array $data): array
    {
        $gross = ($data['daily_rate'] ?? 0) * ($data['days_worked'] ?? 0);

        $data['gross_pay']        = round($gross, 2);
        $data['total_deductions'] = 0;
        $data['net_pay']          = round($gross, 2);

        return $data;
    }
}
