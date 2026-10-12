<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The historical (imported) projects stored their estimated material cost with the 10% material
 * factor already inside each line and the factor itself set to 0%. This restates every line as its
 * base cost with a 10% factor on top — the same way a quotation built in the system is stored — so
 * the factor shows (and is applied) once, never twice.
 *
 * Nothing about the money changes: base × 1.10 equals the old estimate to the centavo (the MS Plates
 * line absorbs any rounding), so the Project Budget, contract, payments, purchases and KPIs are the
 * same before and after. Only rows still at 0% are touched, so running it again does nothing.
 */
return new class extends Migration
{
    private const TAG    = 'Historical record';
    private const FACTOR = 10;

    public function up(): void
    {
        DB::transaction(function () {
            foreach ($this->historicalProjectIds() as $projectId) {
                $lines = DB::table('project_materials')
                    ->where('project_id', $projectId)
                    ->where('status', 'active')
                    ->orderBy('id')
                    ->get();

                if ($lines->isEmpty() || $lines->contains(fn ($l) => (float) $l->factor !== 0.0)) {
                    continue;   // already restated (or edited by hand) — leave it alone
                }

                $estimate  = round($lines->sum(fn ($l) => (float) $l->total_cost), 2);
                $baseTotal = round($estimate / (1 + self::FACTOR / 100), 2);
                $plates    = $lines->firstWhere('material_name', 'MS Plates') ?? $lines->sortByDesc('total_cost')->first();

                $others = 0;
                foreach ($lines as $line) {
                    if ($line->id === $plates->id) {
                        continue;
                    }
                    $base    = round((float) $line->total_cost / (1 + self::FACTOR / 100), 2);
                    $others += $base;
                    $this->saveLine($line, $base);
                }

                // the main line takes whatever is left, so the base lines total exactly estimate ÷ 1.10
                $this->saveLine($plates, round($baseTotal - $others, 2));
            }
        });
    }

    public function down(): void
    {
        DB::transaction(function () {
            foreach ($this->historicalProjectIds() as $projectId) {
                $lines = DB::table('project_materials')
                    ->where('project_id', $projectId)
                    ->where('status', 'active')
                    ->where('factor', self::FACTOR)
                    ->orderBy('id')
                    ->get();

                if ($lines->isEmpty()) {
                    continue;
                }

                $estimate = round($lines->sum(fn ($l) => (float) $l->total_cost) * (1 + self::FACTOR / 100), 2);
                $plates   = $lines->firstWhere('material_name', 'MS Plates') ?? $lines->sortByDesc('total_cost')->first();

                $others = 0;
                foreach ($lines as $line) {
                    if ($line->id === $plates->id) {
                        continue;
                    }
                    $total   = round((float) $line->total_cost * (1 + self::FACTOR / 100), 2);
                    $others += $total;
                    $this->saveLine($line, $total, 0);
                }
                $this->saveLine($plates, round($estimate - $others, 2), 0);
            }
        });
    }

    private function historicalProjectIds()
    {
        return DB::table('projects')->where('notes', 'like', self::TAG . '%')->orderBy('id')->pluck('id');
    }

    private function saveLine(object $line, float $total, float $factor = self::FACTOR): void
    {
        $qty = max((float) $line->quantity, 0.0001);

        DB::table('project_materials')->where('id', $line->id)->update([
            'total_cost'     => $total,
            'price_per_unit' => round($total / $qty, 2),
            'factor'         => $factor,
        ]);
    }
};
