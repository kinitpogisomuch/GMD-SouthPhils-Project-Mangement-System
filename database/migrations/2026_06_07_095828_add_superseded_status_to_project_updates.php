<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add 'superseded' to the allowed status values
        DB::statement('ALTER TABLE project_updates DROP CONSTRAINT IF EXISTS project_updates_status_check');
        DB::statement("ALTER TABLE project_updates ADD CONSTRAINT project_updates_status_check
            CHECK (status IN ('pending_review', 'approved', 'needs_revision', 'superseded'))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE project_updates DROP CONSTRAINT IF EXISTS project_updates_status_check');
        DB::statement("ALTER TABLE project_updates ADD CONSTRAINT project_updates_status_check
            CHECK (status IN ('pending_review', 'approved', 'needs_revision'))");
    }
};
