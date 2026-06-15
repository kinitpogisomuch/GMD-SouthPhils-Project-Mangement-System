<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fund_settings', function (Blueprint $table) {
            $table->decimal('current_balance', 15, 2)->default(0)->after('limit_amount');
            $table->decimal('initial_balance', 15, 2)->default(0)->after('current_balance');
        });

        Schema::table('fund_settings', function (Blueprint $table) {
            $table->dropColumn('limit_amount');
        });
    }

    public function down(): void
    {
        Schema::table('fund_settings', function (Blueprint $table) {
            $table->decimal('limit_amount', 15, 2)->default(0);
        });

        Schema::table('fund_settings', function (Blueprint $table) {
            $table->dropColumn(['current_balance', 'initial_balance']);
        });
    }
};
