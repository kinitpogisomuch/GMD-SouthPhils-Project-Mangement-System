<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_updates', function (Blueprint $table) {
            if (!Schema::hasColumn('project_updates', 'update_label')) {
                $table->string('update_label')->nullable()->after('type');
            }
            if (!Schema::hasColumn('project_updates', 'parent_update_id')) {
                $table->unsignedBigInteger('parent_update_id')->nullable()->after('update_label');
            }
            if (!Schema::hasColumn('project_updates', 'revision_feedback')) {
                $table->text('revision_feedback')->nullable()->after('parent_update_id');
            }
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE project_updates DROP CONSTRAINT IF EXISTS project_updates_status_check');
            DB::statement("ALTER TABLE project_updates ADD CONSTRAINT project_updates_status_check CHECK (status IN ('pending_review', 'approved', 'needs_revision'))");
        }
    }

    public function down(): void
    {
        Schema::table('project_updates', function (Blueprint $table) {
            $table->dropColumn(array_filter([
                Schema::hasColumn('project_updates', 'update_label')      ? 'update_label'      : null,
                Schema::hasColumn('project_updates', 'parent_update_id')  ? 'parent_update_id'  : null,
                Schema::hasColumn('project_updates', 'revision_feedback') ? 'revision_feedback' : null,
            ]));
        });
    }
};