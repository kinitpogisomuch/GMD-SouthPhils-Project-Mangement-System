<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            // Defaults based on researched benchmarks (see reports.blade.php for citations):
            // - Profit margin: PH COA benchmarks contractor margin at 8-10% of Estimated Direct
            //   Cost; general industry gross margin runs 15-30%. 20% is a reasonable business target.
            // - On-time delivery: industry sources report effective contractors hit 80-90%.
            // - Budget adherence: PMI Cost Performance Index (CPI) standard - CPI = 1.0 (100%)
            //   is "on budget"; CPI < 1.0 is over budget. 100% is the correct formula-based target.
            $table->decimal('kpi_profit_margin_target', 5, 2)->default(20)->after('description');
            $table->decimal('kpi_on_time_target', 5, 2)->default(90)->after('kpi_profit_margin_target');
            $table->decimal('kpi_budget_adherence_target', 5, 2)->default(100)->after('kpi_on_time_target');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn(['kpi_profit_margin_target', 'kpi_on_time_target', 'kpi_budget_adherence_target']);
        });
    }
};
