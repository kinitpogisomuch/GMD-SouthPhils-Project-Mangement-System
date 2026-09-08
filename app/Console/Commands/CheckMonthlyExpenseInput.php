<?php

namespace App\Console\Commands;

use App\Models\MonthlyExpense;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class CheckMonthlyExpenseInput extends Command
{
    protected $signature = 'expenses:check-monthly-input';

    protected $description = 'Notify admins if no monthly expense has been recorded yet, 3 days before the current month ends';

    public function handle(): int
    {
        $today = now();

        // Only fire on the day that sits exactly 3 days before the month's last day.
        if ($today->daysInMonth - $today->day !== 3) {
            return self::SUCCESS;
        }

        $monthYear = $today->format('Y-m');

        if (MonthlyExpense::where('month_year', $monthYear)->exists()) {
            return self::SUCCESS;
        }

        NotificationService::monthlyExpenseReminder($monthYear, $today->copy()->endOfMonth()->format('F j, Y'));

        return self::SUCCESS;
    }
}
