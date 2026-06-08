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
        'overtime_hours',
        'sss',
        'philhealth',
        'pagibig',
        'other_deductions',
        'extra_deductions',
        'apply_deductions', // true = deduct SSS/PhilHealth/Pag-IBIG this week
        'gross_pay',
        'total_deductions',
        'net_pay',
        'status',
        'notes',
    ];

    protected $casts = [
        'daily_rate'        => 'decimal:2',
        'days_worked'       => 'decimal:2',
        'overtime_hours'    => 'decimal:2',
        'sss'               => 'decimal:2',
        'philhealth'        => 'decimal:2',
        'pagibig'           => 'decimal:2',
        'other_deductions'  => 'decimal:2',
        'extra_deductions'  => 'decimal:2',
        'gross_pay'         => 'decimal:2',
        'total_deductions'  => 'decimal:2',
        'net_pay'           => 'decimal:2',
        'apply_deductions'  => 'boolean',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public static function compute(array $data): array
    {
        $gross = ($data['daily_rate'] * $data['days_worked'])
               + ($data['overtime_hours'] * ($data['daily_rate'] / 8));

        // Government deductions only applied when apply_deductions is true
        $govDeductions = ($data['apply_deductions'] ?? false)
            ? (($data['sss'] ?? 0) + ($data['philhealth'] ?? 0) + ($data['pagibig'] ?? 0) + ($data['other_deductions'] ?? 0))
            : 0;

        $deductions = $govDeductions + ($data['extra_deductions'] ?? 0);

        $data['gross_pay']        = round($gross, 2);
        $data['total_deductions'] = round($deductions, 2);
        $data['net_pay']          = round($gross - $deductions, 2);

        return $data;
    }
}
