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
        Schema::table('project_materials', function (Blueprint $table) {
            $table->decimal('factor', 5, 2)->default(7)->after('total_cost');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_materials', function (Blueprint $table) {
            $table->dropColumn('factor');
        });
    }
};
