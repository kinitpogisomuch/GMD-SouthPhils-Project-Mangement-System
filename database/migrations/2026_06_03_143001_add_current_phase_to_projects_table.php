<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            if (!Schema::hasColumn('projects', 'current_phase')) {
                $table->enum('current_phase', [
                    'planning','procurement','matl_prep','fabrication',
                    'inspection','painting','completion','delivery'
                ])->default('planning')->after('progress');
            }
            if (!Schema::hasColumn('projects', 'total_phases')) {
                $table->integer('total_phases')->default(8)->after('current_phase');
            }
            if (!Schema::hasColumn('projects', 'completion_percentage')) {
                $table->integer('completion_percentage')->default(0)->after('total_phases');
            }
            if (!Schema::hasColumn('projects', 'phase_completion_status')) {
                $table->json('phase_completion_status')->nullable()->after('completion_percentage');
            }
            if (!Schema::hasColumn('projects', 'last_phase_update_at')) {
                $table->timestamp('last_phase_update_at')->nullable()->after('phase_completion_status');
            }
            if (!Schema::hasColumn('projects', 'last_phase_updated_by')) {
                $table->unsignedBigInteger('last_phase_updated_by')->nullable()->after('last_phase_update_at');
                $table->foreign('last_phase_updated_by')->references('id')->on('users')->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            // Drop foreign key first
            $table->dropForeign(['last_phase_updated_by']);
            
            // Drop the columns
            $table->dropColumn([
                'current_phase',
                'total_phases',
                'completion_percentage',
                'phase_completion_status',
                'last_phase_update_at',
                'last_phase_updated_by'
            ]);
        });
    }
};