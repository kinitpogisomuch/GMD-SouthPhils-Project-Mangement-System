<?php

namespace App\Console\Commands;

use App\Models\MaterialUsage;
use App\Models\Notification;
use App\Models\Project;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class CheckMaterialUsageLogging extends Command
{
    protected $signature = 'materials:check-usage-logging';

    protected $description = 'Notify admins when an active project has gone 5+ days without a material usage log entry';

    public function handle(): int
    {
        Project::where('status', 'ongoing')->each(function (Project $project) {
            $lastLogAt = MaterialUsage::where('project_id', $project->id)->max('created_at');

            // No log yet at all — measure the gap from when the project started.
            $referenceDate = $lastLogAt ? \Carbon\Carbon::parse($lastLogAt) : $project->created_at;
            $daysSince     = (int) $referenceDate->diffInDays(now());

            if ($daysSince < 5) {
                return;
            }

            $lastReminder = Notification::where('user_type', 'admin')
                ->where('notification_type', NotificationService::TYPE_MATERIAL_LOGGING_REMINDER)
                ->where('related_project_id', $project->id)
                ->orderByDesc('created_at')
                ->first();

            // Already reminded since the last real log — only nag again every 5 days.
            if ($lastReminder
                && $lastReminder->created_at->greaterThan($referenceDate)
                && $lastReminder->created_at->diffInDays(now()) < 5) {
                return;
            }

            NotificationService::materialLoggingReminder($project, $daysSince);
        });

        return self::SUCCESS;
    }
}
