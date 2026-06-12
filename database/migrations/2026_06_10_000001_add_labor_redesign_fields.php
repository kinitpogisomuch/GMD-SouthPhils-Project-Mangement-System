<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->decimal('estimated_working_days', 8, 2)->default(0)->after('total_phases');
        });

        Schema::table('project_labor', function (Blueprint $table) {
            $table->decimal('daily_rate', 14, 2)->default(0)->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('estimated_working_days');
        });

        Schema::table('project_labor', function (Blueprint $table) {
            $table->dropColumn('daily_rate');
        });
    }
};
