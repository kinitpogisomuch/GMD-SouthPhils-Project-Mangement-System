<?php

namespace App\Services;

use App\Models\FundSetting;
use App\Models\FundTransaction;
use App\Models\MaterialPurchase;
use App\Models\Payment;
use App\Models\PaymentTransaction;
use App\Models\Project;
use Illuminate\Support\Facades\DB;

/**
 * The money a project actually has to spend. Spending (material purchases, labor, overhead) is paid from
 * what the client has paid so far — not from the Project Budget, which stays a planning figure. When a new
 * expense costs more than that, the revolving fund can cover the difference as a release to the project,
 * which the project's next client payments pay back automatically (FundTransaction::autoReplenish).
 *
 *   Available = Total Received + Fund released to the project − Fund repaid from its payments
 *             − (Material purchases + Labor allocated + Overhead allocated)
 *
 * Fund repayments are taken out because that part of a client payment went back to the fund, and fund
 * releases are added because that money was given to the project to spend.
 */
class ProjectFunds
{
    public static function summary(int $projectId): array
    {
        $paymentIds = Payment::where('project_id', $projectId)->pluck('id');
        $received   = (float) PaymentTransaction::whereIn('payment_id', $paymentIds)->sum('amount_paid');
        $released   = FundTransaction::totalReleased($projectId);
        $repaid     = FundTransaction::totalReplenished($projectId);
        $materials  = (float) MaterialPurchase::where('project_id', $projectId)->sum('total_paid');
        $labor      = (float) DB::table('salary_record_project')->where('project_id', $projectId)->sum('allocated_pay');
        $overhead   = (float) DB::table('monthly_expense_projects')->where('project_id', $projectId)->sum('allocated_amount');
        $spent      = $materials + $labor + $overhead;

        return [
            'received'       => round($received, 2),
            'fund_released'  => round($released, 2),
            'fund_repaid'    => round($repaid, 2),
            'fund_owed'      => round(max(0, $released - $repaid), 2),
            'materials'      => round($materials, 2),
            'labor'          => round($labor, 2),
            'overhead'       => round($overhead, 2),
            'spent'          => round($spent, 2),
            'available'      => round($received + $released - $repaid - $spent, 2),
        ];
    }

    public static function available(int $projectId): float
    {
        return static::summary($projectId)['available'];
    }

    /**
     * How much of a new expense the project can't pay from what it has. Only the new expense is ever
     * covered — if the project was already over before this feature, that older gap isn't added on.
     */
    public static function overrun(int $projectId, float $newSpend): float
    {
        if ($newSpend <= 0) {
            return 0.0;
        }
        $available = static::available($projectId);
        return round(max(0, min($newSpend, $newSpend - $available)), 2);
    }

    /**
     * The overrun of each project for a set of new expenses ([project_id => amount]), for the "exceeds
     * Total Received" modal. Projects that can afford their part are left out.
     *
     * @return array<int, array{project_id:int, project:string, client:string, amount:float, available:float, exceeded:float}>
     */
    public static function overruns(array $newSpendByProject): array
    {
        $rows = [];
        foreach ($newSpendByProject as $projectId => $amount) {
            $amount = round((float) $amount, 2);
            if ($amount <= 0) {
                continue;
            }
            $exceeded = static::overrun((int) $projectId, $amount);
            if ($exceeded > 0) {
                $project = Project::find($projectId);
                $rows[] = [
                    'project_id' => (int) $projectId,
                    'project'    => $project->name ?? 'Project #' . $projectId,
                    'client'     => $project->live_client_name ?? '',
                    'amount'     => $amount,
                    'available'  => max(0, static::available((int) $projectId)),
                    'exceeded'   => $exceeded,
                ];
            }
        }
        return $rows;
    }

    /** What the "exceeds Total Received" modal shows — the projects over, the total, and the fund balance. */
    public static function overrunPayload(array $overruns): array
    {
        $total   = round(array_sum(array_column($overruns, 'exceeded')), 2);
        $balance = FundSetting::getCurrentBalance();

        return [
            'projects'      => $overruns,
            'total'         => $total,
            'fund_balance'  => round($balance, 2),
            'fund_enough'   => $balance + 0.005 >= $total,
        ];
    }

    /**
     * Releases $amount from the revolving fund to the project to cover an overrun. Must run inside a
     * database transaction: the fund balance row is locked, so two saves at the same moment can never
     * spend the same money twice. Throws when the fund doesn't have enough.
     */
    public static function coverWithFund(int $projectId, float $amount, string $purpose, string $sourceType, ?string $sourceRef = null, ?string $date = null): FundTransaction
    {
        $setting = FundSetting::query()->lockForUpdate()->first() ?? FundSetting::instance();
        $balance = (float) $setting->current_balance;

        if ($amount > $balance + 0.005) {
            throw new \DomainException('The revolving fund only has ₱' . number_format($balance, 2)
                . ' left, which is not enough to cover ₱' . number_format($amount, 2) . '. Top up the fund on the Revolving Fund page first.');
        }

        $newBalance = round($balance - $amount, 2);
        $setting->update(['current_balance' => $newBalance]);

        $release = FundTransaction::create([
            'type'          => 'release',
            'amount'        => round($amount, 2),
            'date'          => $date ?? now()->format('Y-m-d'),
            'project_id'    => $projectId,
            'purpose'       => $purpose,
            'description'   => $purpose,
            'status'        => 'Pending Replenishment',
            'balance_after' => $newBalance,
            'recorded_by'   => auth()->user()->name ?? 'Admin',
            'source_type'   => $sourceType,
            'source_ref'    => $sourceRef,
        ]);

        $project = Project::find($projectId);
        if ($project) {
            DB::afterCommit(fn () => NotificationService::revolvingFundReleased($project, (float) $release->amount, $purpose));
        }

        return $release;
    }

    /**
     * Covers every project's overrun from the fund (inside the caller's transaction). The overruns are
     * worked out again here, under the lock, so what is released always matches the latest figures.
     *
     * @return FundTransaction[] keyed by project id
     */
    public static function coverOverruns(array $newSpendByProject, string $purposePrefix, string $sourceType, ?string $sourceRef = null, ?string $date = null): array
    {
        FundSetting::query()->lockForUpdate()->first();   // take the lock before reading the figures

        $overruns = static::overruns($newSpendByProject);
        $total    = array_sum(array_column($overruns, 'exceeded'));
        $balance  = FundSetting::getCurrentBalance();
        if ($total > $balance + 0.005) {
            throw new \DomainException('The revolving fund only has ₱' . number_format($balance, 2)
                . ' left, which is not enough to cover the ₱' . number_format($total, 2) . ' over. Top up the fund on the Revolving Fund page first.');
        }

        $releases = [];
        foreach ($overruns as $row) {
            $releases[$row['project_id']] = static::coverWithFund($row['project_id'], $row['exceeded'], $purposePrefix, $sourceType, $sourceRef, $date);
        }
        return $releases;
    }

    /**
     * A purchase the fund paid for was deleted: give back the part of its release that the project
     * hasn't repaid yet (never more than the purchase cost), recorded as a repayment to the fund.
     */
    public static function returnForDeletedPurchase(MaterialPurchase $purchase): ?FundTransaction
    {
        if (!$purchase->fund_transaction_id) {
            return null;
        }
        $release = FundTransaction::find($purchase->fund_transaction_id);
        if (!$release || $release->type !== 'release') {
            return null;
        }

        $setting  = FundSetting::query()->lockForUpdate()->first() ?? FundSetting::instance();
        $owed     = FundTransaction::outstandingForProject((int) $purchase->project_id);
        $amount   = round(min((float) $purchase->total_paid, (float) $release->amount, $owed), 2);
        if ($amount <= 0) {
            return null;
        }

        $newBalance = round((float) $setting->current_balance + $amount, 2);
        $setting->update(['current_balance' => $newBalance]);

        $purpose = 'Returned to the fund — purchase of ' . $purchase->material_name . ' deleted';
        $repayment = FundTransaction::create([
            'type'          => 'replenishment',
            'amount'        => $amount,
            'date'          => now()->format('Y-m-d'),
            'project_id'    => $purchase->project_id,
            'purpose'       => $purpose,
            'description'   => $purpose,
            'status'        => 'Completed',
            'balance_after' => $newBalance,
            'recorded_by'   => auth()->user()->name ?? 'Admin',
            'source_type'   => 'purchase_deleted',
            'source_ref'    => (string) $purchase->id,
        ]);
        FundTransaction::syncReleaseStatuses((int) $purchase->project_id);

        return $repayment;
    }
}
