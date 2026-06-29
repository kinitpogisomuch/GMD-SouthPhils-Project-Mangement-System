<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalaryRecord extends Model
{
    protected $fillable = [
        'employee_id',
        'project_id',
        'pay_period',
        'daily_rate',
        'days_worked',
        'overtime_hours',
        'gross_pay',
        'total_deductions',
        'net_pay',
        'notes',
    ];

    protected $casts = [
        'daily_rate'        => 'decimal:2',
        'days_worked'       => 'decimal:2',
        'overtime_hours'    => 'decimal:2',
        'gross_pay'         => 'decimal:2',
        'total_deductions'  => 'decimal:2',
        'net_pay'           => 'decimal:2',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function projects()
    {
        return $this->belongsToMany(Project::class, 'salary_record_project');
    }

    public static function compute(array $data): array
    {
        $dailyRate     = (float) ($data['daily_rate']     ?? 0);
        $daysWorked    = (float) ($data['days_worked']    ?? 0);
        $overtimeHours = (float) ($data['overtime_hours'] ?? 0);

        $regularPay   = $dailyRate * $daysWorked;
        $hourlyRate   = $dailyRate / 8;               // daily rate ÷ 8 hours
        $overtimePay  = $overtimeHours * $hourlyRate; // overtime at straight hourly rate
        $gross        = $regularPay + $overtimePay;

        $data['overtime_hours']   = $overtimeHours;
        $data['gross_pay']        = round($gross, 2);
        $data['total_deductions'] = 0;
        $data['net_pay']          = round($gross, 2);

        return $data;
    }
}
