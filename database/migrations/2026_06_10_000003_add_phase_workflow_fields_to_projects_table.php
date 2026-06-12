<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            if (!Schema::hasColumn('projects', 'current_sub_phase')) {
                $table->string('current_sub_phase')->nullable()->after('current_phase');
            }
            if (!Schema::hasColumn('projects', 'phase_data')) {
                $table->json('phase_data')->nullable()->after('phase_completion_status');
            }
        });

        DB::table('projects')
            ->where('current_phase', 'planning')
            ->whereNull('current_sub_phase')
            ->update(['current_sub_phase' => 'shop_drawing']);
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            if (Schema::hasColumn('projects', 'current_sub_phase')) {
                $table->dropColumn('current_sub_phase');
            }
            if (Schema::hasColumn('projects', 'phase_data')) {
                $table->dropColumn('phase_data');
            }
        });
    }
};
