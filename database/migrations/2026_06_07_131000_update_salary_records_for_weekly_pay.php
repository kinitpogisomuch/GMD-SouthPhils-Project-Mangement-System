<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salary_records', function (Blueprint $table) {
            // Change pay_period from "2026-05" to week-start date "2026-06-02" (Monday)
            $table->string('pay_period', 10)->change();

            // Whether to apply monthly government deductions (SSS/PhilHealth/Pag-IBIG) this week
            $table->boolean('apply_deductions')->default(false)->after('extra_deductions');
        });
    }

    public function down(): void
    {
        Schema::table('salary_records', function (Blueprint $table) {
            $table->string('pay_period', 7)->change();
            $table->dropColumn('apply_deductions');
        });
    }
};
