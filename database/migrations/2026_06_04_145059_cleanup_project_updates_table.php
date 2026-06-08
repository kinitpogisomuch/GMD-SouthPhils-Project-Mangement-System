<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Ensure the status column exists before later migrations add a CHECK constraint on it
        if (!Schema::hasColumn('project_updates', 'status')) {
            Schema::table('project_updates', function (Blueprint $table) {
                $table->string('status')->default('pending_review');
            });
        }

        Schema::table('project_updates', function (Blueprint $table) {
            $columns = ['update_type', 'approval_status', 'approved_at', 'approved_by_admin_id', 'revision_comment', 'revision_requested_at'];
            foreach ($columns as $col) {
                if (Schema::hasColumn('project_updates', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }

    public function down(): void
    {
        //
    }
};