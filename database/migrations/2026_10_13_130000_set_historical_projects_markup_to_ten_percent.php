<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Every historical (imported) project gets the standard 10% markup.
 *
 * Their contracts are fixed by the payments actually received, so the contract, down payment and
 * payment records are NOT touched. Instead the Project Budget is set to contract ÷ 1.10 (markup =
 * the other 10%), and the estimated materials are rescaled so materials (+ 10% factor) + the
 * unchanged estimated labor equal that budget. Purchases, salaries, revenue and net profit stay
 * exactly as they were; only the estimate (and with it Budget Adherence) moves.
 *
 * The original budget, markup and material lines are kept in historical_markup_backup so down()
 * puts everything back exactly. Running it again changes nothing.
 */
return new class extends Migration
{
    private const TAG    = 'Historical record';
    private const MARKUP = 10;   // %

    public function up(): void
    {
        if (!Schema::hasTable('historical_markup_backup')) {
            Schema::create('historical_markup_backup', function (Blueprint $table) {
                $table->unsignedBigInteger('project_id')->primary();
                $table->decimal('project_budget', 15, 2);
                $table->decimal('markup', 15, 2);
                $table->json('materials');   // [{id, total_cost, price_per_unit}]
                $table->timestamps();
            });
        }

        DB::transaction(function () {
            $projectIds = DB::table('projects')->where('notes', 'like', self::TAG . '%')->orderBy('id')->pluck('id');

            foreach ($projectIds as $projectId) {
                $payment = DB::table('payments')->where('project_id', $projectId)->first();
                if (!$payment || (float) $payment->contract_amount <= 0) {
                    continue;
                }

                $contract = (float) $payment->contract_amount;
                $budget   = round($contract / (1 + self::MARKUP / 100), 2);
                $markup   = round($contract - $budget, 2);
                if (abs((float) $payment->project_budget - $budget) < 0.005 && abs((float) $payment->markup - $markup) < 0.005) {
                    continue;   // already at 10%
                }

                $lines = DB::table('project_materials')->where('project_id', $projectId)->where('status', 'active')->orderBy('id')->get();
                $labor = (float) DB::table('project_labor')->where('project_id', $projectId)->where('status', 'active')->sum('total_cost');
                $factor = (float) ($lines->first()->factor ?? 10);
                $oldBase = round($lines->sum(fn ($l) => (float) $l->total_cost), 2);
                $newBase = round(($budget - $labor) / (1 + $factor / 100), 2);
                if ($lines->isEmpty() || $oldBase <= 0 || $newBase <= 0) {
                    continue;
                }

                DB::table('historical_markup_backup')->insertOrIgnore([
                    'project_id'     => $projectId,
                    'project_budget' => $payment->project_budget,
                    'markup'         => $payment->markup,
                    'materials'      => json_encode($lines->map(fn ($l) => ['id' => $l->id, 'total_cost' => $l->total_cost, 'price_per_unit' => $l->price_per_unit])->values()),
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);

                // every line scaled by the same ratio; the largest line takes the rounding
                $ratio = $newBase / $oldBase;
                $main  = $lines->firstWhere('material_name', 'MS Plates') ?? $lines->sortByDesc('total_cost')->first();
                $sum   = 0;
                foreach ($lines as $line) {
                    if ($line->id === $main->id) {
                        continue;
                    }
                    $total = round((float) $line->total_cost * $ratio, 2);
                    $sum  += $total;
                    $this->saveLine($line, $total);
                }
                $this->saveLine($main, round($newBase - $sum, 2));

                DB::table('payments')->where('id', $payment->id)->update([
                    'project_budget' => $budget,
                    'markup'         => $markup,
                ]);
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('historical_markup_backup')) {
            return;
        }

        DB::transaction(function () {
            foreach (DB::table('historical_markup_backup')->get() as $backup) {
                DB::table('payments')->where('project_id', $backup->project_id)->update([
                    'project_budget' => $backup->project_budget,
                    'markup'         => $backup->markup,
                ]);
                foreach (json_decode($backup->materials, true) as $line) {
                    DB::table('project_materials')->where('id', $line['id'])->update([
                        'total_cost'     => $line['total_cost'],
                        'price_per_unit' => $line['price_per_unit'],
                    ]);
                }
            }
        });

        Schema::drop('historical_markup_backup');
    }

    private function saveLine(object $line, float $total): void
    {
        DB::table('project_materials')->where('id', $line->id)->update([
            'total_cost'     => $total,
            'price_per_unit' => round($total / max((float) $line->quantity, 0.0001), 2),
        ]);
    }
};
