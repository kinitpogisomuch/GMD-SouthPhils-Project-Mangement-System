<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salary_records', function (Blueprint $table) {
            $table->decimal('half_days', 5, 2)->default(0)->after('days_worked');
        });
    }

    public function down(): void
    {
        Schema::table('salary_records', function (Blueprint $table) {
            $table->dropColumn('half_days');
        });
    }
};
