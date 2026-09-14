<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->decimal('project_budget', 15, 2)->nullable()->after('contract_amount');
            $table->decimal('markup', 15, 2)->default(0)->after('project_budget');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['project_budget', 'markup']);
        });
    }
};
