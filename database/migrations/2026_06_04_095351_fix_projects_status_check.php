<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Drop the old status check constraint
        DB::statement('ALTER TABLE projects DROP CONSTRAINT IF EXISTS projects_status_check');

        // Add new constraint that includes all valid values
        DB::statement("ALTER TABLE projects ADD CONSTRAINT projects_status_check CHECK (status IN ('pending', 'ongoing', 'completed', 'planning'))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE projects DROP CONSTRAINT IF EXISTS projects_status_check');
        DB::statement("ALTER TABLE projects ADD CONSTRAINT projects_status_check CHECK (status IN ('pending', 'ongoing', 'completed'))");
    }
};