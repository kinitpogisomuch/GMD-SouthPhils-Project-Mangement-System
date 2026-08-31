<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('project_templates', function (Blueprint $table) {
            $table->foreignId('project_id')->nullable()->after('id')->constrained('projects')->nullOnDelete();
        });

        $this->backfillProjectLinks();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_templates', function (Blueprint $table) {
            $table->dropConstrainedForeignId('project_id');
        });
    }

    /**
     * Link existing templates (created before this column existed) back to the project
     * whose tank specs they were snapshotted from, so material edits on that project can
     * sync into the template going forward.
     */
    private function backfillProjectLinks(): void
    {
        $templates = DB::table('project_templates')->whereNull('project_id')->get();
        if ($templates->isEmpty()) {
            return;
        }

        $projects = DB::table('projects')->orderBy('created_at')->get(['id', 'created_at']);

        foreach ($templates as $template) {
            $templateItems = $this->normalizeTankItems(json_decode($template->tank_items, true));
            if (empty($templateItems)) {
                continue;
            }

            foreach ($projects as $project) {
                $projectItems = $this->normalizeTankItems(
                    DB::table('project_tank_items')
                        ->where('project_id', $project->id)
                        ->orderBy('sort_order')
                        ->get(['tank_type', 'capacity', 'dimensions', 'quantity'])
                        ->map(fn ($t) => (array) $t)
                        ->toArray()
                );

                if ($projectItems === $templateItems) {
                    DB::table('project_templates')->where('id', $template->id)->update(['project_id' => $project->id]);
                    break;
                }
            }
        }
    }

    private function normalizeTankItems($items): array
    {
        return collect($items ?? [])->map(fn ($item) => [
            'tank_type'  => $item['tank_type']  ?? null,
            'capacity'   => $item['capacity']   ?? null,
            'dimensions' => $item['dimensions'] ?? null,
            'quantity'   => (int) ($item['quantity'] ?? 1),
        ])->values()->toArray();
    }
};
