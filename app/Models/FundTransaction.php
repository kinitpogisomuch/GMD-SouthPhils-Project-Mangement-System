<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Services\NotificationService;

class FundTransaction extends Model
{
    protected $fillable = [
        'type',
        'amount',
        'date',
        'project_id',
        'material_request_id',
        'purpose',
        'description',
        'remarks',
        'status',
        'balance_after',
        'recorded_by',
    ];

    protected $casts = [
        'amount'        => 'decimal:2',
        'balance_after' => 'decimal:2',
        'date'          => 'date',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function materialRequest()
    {
        return $this->belongsTo(MaterialRequest::class);
    }

    public static function totalReleased(?int $projectId = null): float
    {
        $query = static::where('type', 'release');

        if ($projectId !== null) {
            $query->where('project_id', $projectId);
        }

        return (float) $query->sum('amount');
    }

    public static function totalReplenished(?int $projectId = null): float
    {
        $query = static::where('type', 'replenishment');

        if ($projectId !== null) {
            $query->where('project_id', $projectId);
        }

        return (float) $query->sum('amount');
    }

    public static function outstandingForProject(int $projectId): float
    {
        return static::totalReleased($projectId) - static::totalReplenished($projectId);
    }

    public static function activeProjectAdvancesCount(): int
    {
        $released    = static::where('type', 'release')->groupBy('project_id')->selectRaw('project_id, SUM(amount) as total')->pluck('total', 'project_id');
        $replenished = static::where('type', 'replenishment')->groupBy('project_id')->selectRaw('project_id, SUM(amount) as total')->pluck('total', 'project_id');

        $count = 0;
        foreach ($released as $projectId => $totalReleased) {
            if ($projectId === null) {
                continue;
            }

            $outstanding = (float) $totalReleased - (float) ($replenished[$projectId] ?? 0);

            if ($outstanding > 0) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Automatically replenish the revolving fund when a project payment is
     * recorded, up to that project's outstanding released amount.
     */
    public static function autoReplenish(Project $project, float $paymentAmount, string $stageLabel): ?self
    {
        $outstanding = static::outstandingForProject($project->id);

        if ($outstanding <= 0) {
            return null;
        }

        $amount     = min($outstanding, $paymentAmount);
        $newBalance = FundSetting::adjustBalance($amount);
        $purpose    = "Auto-replenishment from {$stageLabel}";

        $transaction = static::create([
            'type'         => 'replenishment',
            'amount'       => $amount,
            'date'         => now()->format('Y-m-d'),
            'project_id'   => $project->id,
            'purpose'      => $purpose,
            'description'  => $purpose,
            'status'       => 'Completed',
            'balance_after'=> $newBalance,
            'recorded_by'  => 'System',
        ]);

        NotificationService::revolvingFundReplenished($project, $amount);

        return $transaction;
    }
}
