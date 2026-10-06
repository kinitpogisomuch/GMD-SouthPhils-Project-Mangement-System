<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * On-time delivery is now judged as a rate (projects delivered on time ÷ completed
 * projects × 100) against a target rate in percent, instead of a number of projects.
 *
 * The old values were project counts ("5 projects"), which can't be turned into a
 * percent, so they are cleared — the admin re-enters the target as a rate in
 * "Set KPI targets". The target is one rate per quarter; the three monthly columns
 * keep a copy of it so a month view is judged against the same rate.
 */
return new class extends Migration
{
    private array $columns = ['on_time_target', 'on_time_target_m1', 'on_time_target_m2', 'on_time_target_m3'];

    public function up(): void
    {
        foreach ($this->columns as $col) {
            DB::statement("ALTER TABLE kpi_quarter_targets ALTER COLUMN {$col} DROP DEFAULT");
            DB::statement("ALTER TABLE kpi_quarter_targets ALTER COLUMN {$col} DROP NOT NULL");
            DB::statement("ALTER TABLE kpi_quarter_targets ALTER COLUMN {$col} TYPE numeric(5,2) USING NULL");
        }
    }

    public function down(): void
    {
        foreach ($this->columns as $col) {
            DB::statement("ALTER TABLE kpi_quarter_targets ALTER COLUMN {$col} TYPE integer USING 0");
            DB::statement("ALTER TABLE kpi_quarter_targets ALTER COLUMN {$col} SET DEFAULT 0");
            DB::statement("UPDATE kpi_quarter_targets SET {$col} = 0 WHERE {$col} IS NULL");
            DB::statement("ALTER TABLE kpi_quarter_targets ALTER COLUMN {$col} SET NOT NULL");
        }
    }
};
