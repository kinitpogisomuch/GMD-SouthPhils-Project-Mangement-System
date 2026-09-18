<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Work Done is now optional on the employee progress-update form —
        // Site Photos became the required field instead.
        DB::statement('ALTER TABLE project_updates ALTER COLUMN work_done DROP NOT NULL');
    }

    public function down(): void
    {
        DB::statement("UPDATE project_updates SET work_done = '' WHERE work_done IS NULL");
        DB::statement('ALTER TABLE project_updates ALTER COLUMN work_done SET NOT NULL');
    }
};
