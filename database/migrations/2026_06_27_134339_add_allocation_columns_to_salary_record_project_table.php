<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('salary_record_project', function (Blueprint $table) {
            $table->decimal('days_worked',    5, 2)->default(0)->after('project_id');
            $table->decimal('overtime_hours', 5, 2)->default(0)->after('days_worked');
            $table->decimal('allocated_pay',  10, 2)->default(0)->after('overtime_hours');
        });
    }

    public function down(): void
    {
        Schema::table('salary_record_project', function (Blueprint $table) {
            $table->dropColumn(['days_worked', 'overtime_hours', 'allocated_pay']);
        });
    }
};
