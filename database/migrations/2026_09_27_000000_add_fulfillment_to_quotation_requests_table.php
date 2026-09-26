<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Each tank on a quotation request is either delivered (with a delivery address) or picked up by
     * the client (no address). Existing requests were all delivery requests, so they default to it.
     * `location` therefore has to allow being empty for a pick-up tank.
     */
    public function up(): void
    {
        Schema::table('quotation_requests', function (Blueprint $table) {
            $table->string('fulfillment', 20)->default('delivery')->after('target_timeline');
        });

        DB::statement('ALTER TABLE quotation_requests ALTER COLUMN location DROP NOT NULL');
    }

    public function down(): void
    {
        DB::table('quotation_requests')->whereNull('location')->update(['location' => '']);
        DB::statement('ALTER TABLE quotation_requests ALTER COLUMN location SET NOT NULL');

        Schema::table('quotation_requests', function (Blueprint $table) {
            $table->dropColumn('fulfillment');
        });
    }
};
