<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop FK — submitted_by must hold either users.id or employees.id
        Schema::table('project_updates', function (Blueprint $table) {
            $table->dropForeign('project_updates_submitted_by_foreign');
        });

        // Add discriminator column so the model knows which table to resolve
        Schema::table('project_updates', function (Blueprint $table) {
            $table->string('submitted_by_type', 20)->default('user')->after('submitted_by');
        });

        // Back-fill existing rows
        DB::statement("
            UPDATE project_updates
            SET submitted_by_type = CASE
                WHEN type = 'employee_submission' THEN 'employee'
                ELSE 'user'
            END
        ");
    }

    public function down(): void
    {
        Schema::table('project_updates', function (Blueprint $table) {
            $table->dropColumn('submitted_by_type');
            $table->foreign('submitted_by')->references('id')->on('users')->onDelete('set null');
        });
    }
};
