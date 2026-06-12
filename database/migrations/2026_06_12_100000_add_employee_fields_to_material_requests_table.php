<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('material_requests', function (Blueprint $table) {
            $table->foreignId('project_id')->nullable()->after('id')->constrained('projects')->nullOnDelete();
            $table->foreignId('project_material_id')->nullable()->after('project_id')->constrained('project_materials')->nullOnDelete();
            $table->foreignId('requested_by')->nullable()->after('project_material_id')->constrained('employees')->nullOnDelete();
            $table->text('notes')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('material_requests', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
            $table->dropForeign(['project_material_id']);
            $table->dropForeign(['requested_by']);
            $table->dropColumn(['project_id', 'project_material_id', 'requested_by', 'notes']);
        });
    }
};
