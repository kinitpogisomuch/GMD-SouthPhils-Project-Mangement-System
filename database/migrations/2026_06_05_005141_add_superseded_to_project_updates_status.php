<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Drop existing check constraint on progress_requests status and add revision_requested
        DB::statement("ALTER TABLE progress_requests DROP CONSTRAINT IF EXISTS progress_requests_status_check");
        DB::statement("ALTER TABLE progress_requests ADD CONSTRAINT progress_requests_status_check
            CHECK (status IN ('open', 'completed', 'revision_requested'))");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE progress_requests DROP CONSTRAINT IF EXISTS progress_requests_status_check");
        DB::statement("ALTER TABLE progress_requests ADD CONSTRAINT progress_requests_status_check
            CHECK (status IN ('open', 'completed'))");
    }
};