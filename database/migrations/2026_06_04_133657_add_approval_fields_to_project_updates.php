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
            // Add new columns if they don't exist
            if (!Schema::hasColumn('project_updates', 'update_type')) {
                $table->string('update_type')->default('regular')->after('type');
            }
            
            if (!Schema::hasColumn('project_updates', 'approval_status')) {
                $table->string('approval_status')->default('approved')->after('status');
            }
            
            if (!Schema::hasColumn('project_updates', 'revision_feedback')) {
                $table->text('revision_feedback')->nullable()->after('approval_status');
            }
            
            if (!Schema::hasColumn('project_updates', 'requested_by_admin_id')) {
                $table->unsignedBigInteger('requested_by_admin_id')->nullable()->after('revision_feedback');
            }
            
            if (!Schema::hasColumn('project_updates', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('requested_by_admin_id');
            }
            
            if (!Schema::hasColumn('project_updates', 'approved_by_admin_id')) {
                $table->unsignedBigInteger('approved_by_admin_id')->nullable()->after('approved_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('project_updates', function (Blueprint $table) {
            $columns = [
                'update_type', 'approval_status', 'revision_feedback',
                'requested_by_admin_id', 'approved_at', 'approved_by_admin_id'
            ];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('project_updates', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};