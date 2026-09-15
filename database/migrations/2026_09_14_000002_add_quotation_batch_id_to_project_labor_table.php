<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_labor', function (Blueprint $table) {
            $table->uuid('quotation_batch_id')->nullable()->after('project_id');
            $table->foreign('quotation_batch_id')->references('id')->on('quotation_batches')->cascadeOnDelete();
        });

        DB::statement('ALTER TABLE project_labor ALTER COLUMN project_id DROP NOT NULL');
    }

    public function down(): void
    {
        Schema::table('project_labor', function (Blueprint $table) {
            $table->dropForeign(['quotation_batch_id']);
            $table->dropColumn('quotation_batch_id');
        });

        DB::statement('ALTER TABLE project_labor ALTER COLUMN project_id SET NOT NULL');
    }
};
