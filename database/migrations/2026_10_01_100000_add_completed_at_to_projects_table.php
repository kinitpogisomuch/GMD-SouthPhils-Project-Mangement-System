<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * When a project was actually finished. Reports and the KPI dashboard used updated_at for
     * this, which moves every time the project is edited and ignores a backdated delivery.
     */
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->timestamp('completed_at')->nullable()->after('status');
        });

        // Existing completed projects: the delivery's Date of Work, or updated_at if there is none
        foreach (DB::table('projects')->where('status', 'completed')->get(['id', 'updated_at']) as $project) {
            $deliveredOn = DB::table('project_updates')
                ->where('project_id', $project->id)
                ->where('phase', 'delivery')
                ->whereNotNull('date_of_work')
                ->orderByDesc('date_of_work')
                ->value('date_of_work');

            DB::table('projects')->where('id', $project->id)->update([
                'completed_at' => $deliveredOn ?: $project->updated_at,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('completed_at');
        });
    }
};
