<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('progress_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('target_employee_id')->nullable()->after('fulfilled_by');
            $table->foreign('target_employee_id')->references('id')->on('employees')->onDelete('set null');
            $table->index('target_employee_id');
        });
    }

    public function down(): void
    {
        Schema::table('progress_requests', function (Blueprint $table) {
            $table->dropForeign('progress_requests_target_employee_id_foreign');
            $table->dropIndex(['target_employee_id']);
            $table->dropColumn('target_employee_id');
        });
    }
};
