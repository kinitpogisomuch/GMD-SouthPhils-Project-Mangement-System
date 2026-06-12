<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->decimal('contract_amount', 15, 2)->nullable()->after('notes');
            $table->string('project_type')->nullable()->after('contract_amount'); // big_project | small_project
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['contract_amount', 'project_type']);
        });
    }
};
