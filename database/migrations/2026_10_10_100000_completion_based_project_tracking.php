<?php

use App\Models\Project;
use App\Models\ProjectTemplate;
use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Project completion is now based on the project's phases, not its delivery:
     * a project is completed once its Completion phase is done, and delivery is
     * tracked separately (phase_data.delivery). completed_at — the date the
     * On-Time Completion Rate KPI judges against end_date — used to be the
     * delivery date, so existing records are brought in line:
     *
     *   - completed projects: completed_at moves to the Completion phase's Date of
     *     Work (when one is recorded), and a recorded delivery is stamped into
     *     phase_data.delivery.delivered_at so delivery keeps its own date;
     *   - projects that finished Completion but were still waiting on Delivery are
     *     now completed (status, completed_at, 100% progress), awaiting delivery.
     *
     * No new columns — reuses status, completed_at, progress and phase_data.
     */
    public function up(): void
    {
        Project::where('status', '!=', 'archived')
            ->where(fn ($q) => $q->where('status', 'completed')->orWhere('current_phase', 'delivery'))
            ->get()
            ->each(function (Project $project) {
                $completedOn = $this->phaseDate($project, 'completion');
                $deliveredOn = $this->phaseDate($project, 'delivery');
                $wasCompleted = $project->status === 'completed';

                // Delivery keeps its own date. A completed project from before this change
                // that has delivery photos but no delivered_at was completed BY its delivery,
                // so its old completed_at is that delivery date.
                $delivery = $project->phaseData('delivery') ?: [];
                if (empty($delivery['delivered_at'])) {
                    $deliveredAt = $deliveredOn
                        ?? (!empty($delivery) && $project->completed_at ? $project->completed_at->toDateString() : null);
                    if ($deliveredAt) {
                        $delivery['delivered_at'] = $deliveredAt;
                    }
                }
                if (!empty($delivery)) {
                    $data = $project->phase_data ?? [];
                    $data['delivery'] = $delivery;
                    $project->phase_data = $data;
                }

                if ($wasCompleted) {
                    // completed_at = when the Completion phase was finished
                    if ($completedOn) {
                        $project->completed_at = $this->atTimeOf($completedOn, $project->completed_at);
                    }
                } else {
                    // Finished Completion, still waiting on Delivery → completed, awaiting delivery
                    $project->status       = 'completed';
                    $project->progress     = 100;
                    $project->completed_at = $this->atTimeOf(
                        $completedOn ?? ($project->last_phase_update_at ?? $project->updated_at)->toDateString(),
                        null
                    );
                }

                // saveQuietly: the completion dates are set explicitly here, so the model's
                // saving hook (which stamps now()) must not run.
                $project->saveQuietly();

                // Newly completed projects get their reusable template, as any completion does
                if (!$wasCompleted) {
                    try {
                        ProjectTemplate::snapshotFromCompletedProject($project);
                    } catch (\Throwable $e) {
                        // a template is a convenience — never block the data fix over it
                    }
                }
            });
    }

    /**
     * Best-effort reversal: completed-but-undelivered projects go back to the
     * Delivery phase as ongoing, and delivered ones take their delivery date as
     * completed_at again.
     */
    public function down(): void
    {
        Project::where('status', 'completed')->get()->each(function (Project $project) {
            if ($project->isDelivered()) {
                if ($deliveredAt = $project->phaseData('delivery.delivered_at')) {
                    $project->completed_at = $this->atTimeOf($deliveredAt, $project->completed_at);
                }
            } else {
                $project->status       = 'ongoing';
                $project->progress     = 95;
                $project->completed_at = null;
            }
            $project->saveQuietly();
        });
    }

    /**
     * Date (Y-m-d) a phase was finished: the latest approved update logged for that
     * phase, else the phase's approval in phase_completion_status (seeded/legacy data).
     */
    private function phaseDate(Project $project, string $phase): ?string
    {
        $fromUpdates = DB::table('project_updates')
            ->where('project_id', $project->id)
            ->where('phase', $phase)
            ->where('status', 'approved')
            ->whereNotNull('date_of_work')
            ->max('date_of_work');

        if ($fromUpdates) {
            return Carbon::parse($fromUpdates)->toDateString();
        }

        foreach ($project->phase_completion_status ?? [] as $entry) {
            if (($entry['phase'] ?? null) === $phase && !empty($entry['approved']) && !empty($entry['approved_at'])) {
                return Carbon::parse($entry['approved_at'])->toDateString();
            }
        }

        return null;
    }

    /** The given date, keeping the time of day from an existing timestamp (noon if none) */
    private function atTimeOf(string $date, ?Carbon $existing): Carbon
    {
        $day = Carbon::parse($date);

        return $existing ? $day->setTimeFrom($existing) : $day->setTime(12, 0);
    }
};
