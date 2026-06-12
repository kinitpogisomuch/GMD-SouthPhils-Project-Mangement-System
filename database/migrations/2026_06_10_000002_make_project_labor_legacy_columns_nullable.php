<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE project_labor ALTER COLUMN hours DROP NOT NULL');
        DB::statement('ALTER TABLE project_labor ALTER COLUMN hours SET DEFAULT 0');
        DB::statement('ALTER TABLE project_labor ALTER COLUMN rate_per_hour DROP NOT NULL');
        DB::statement('ALTER TABLE project_labor ALTER COLUMN rate_per_hour SET DEFAULT 0');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE project_labor ALTER COLUMN hours DROP DEFAULT');
        DB::statement('ALTER TABLE project_labor ALTER COLUMN hours SET NOT NULL');
        DB::statement('ALTER TABLE project_labor ALTER COLUMN rate_per_hour DROP DEFAULT');
        DB::statement('ALTER TABLE project_labor ALTER COLUMN rate_per_hour SET NOT NULL');
    }
};
