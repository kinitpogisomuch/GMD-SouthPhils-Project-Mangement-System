<?php

namespace App\Console\Commands;

use App\Models\SalaryRecord;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class CheckSalaryRecorded extends Command
{
    protected $signature = 'salary:check-recorded';

    protected $description = 'Notify admins on Saturday if no salary has been recorded yet for the current pay period';

    public function handle(): int
    {
        $payPeriod = now()->startOfWeek(\Carbon\Carbon::MONDAY)->format('Y-m-d');

        if (SalaryRecord::where('pay_period', $payPeriod)->exists()) {
            return self::SUCCESS;
        }

        NotificationService::salaryRecordingReminder($payPeriod);

        return self::SUCCESS;
    }
}
