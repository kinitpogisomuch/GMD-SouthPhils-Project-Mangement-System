<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Monthly breakdown behind each quarter's profit / on-time targets.
     * profit_target / on_time_target on this table remain the quarterly totals
     * (now auto-summed from these three months instead of entered directly).
     */
    public function up(): void
    {
        Schema::table('kpi_quarter_targets', function (Blueprint $table) {
            $table->decimal('profit_target_m1', 14, 2)->default(0)->after('profit_target');
            $table->decimal('profit_target_m2', 14, 2)->default(0)->after('profit_target_m1');
            $table->decimal('profit_target_m3', 14, 2)->default(0)->after('profit_target_m2');
            $table->unsignedInteger('on_time_target_m1')->default(0)->after('on_time_target');
            $table->unsignedInteger('on_time_target_m2')->default(0)->after('on_time_target_m1');
            $table->unsignedInteger('on_time_target_m3')->default(0)->after('on_time_target_m2');
        });
    }

    public function down(): void
    {
        Schema::table('kpi_quarter_targets', function (Blueprint $table) {
            $table->dropColumn([
                'profit_target_m1', 'profit_target_m2', 'profit_target_m3',
                'on_time_target_m1', 'on_time_target_m2', 'on_time_target_m3',
            ]);
        });
    }
};
