<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The Quotation Builder now prices one tank and multiplies it by the number of identical tanks:
 * the tank type and capacity decide which templates are offered, and tank_quantity is the
 * multiplier. Existing quotations were priced for the whole job, so they keep a quantity of 1
 * (their totals don't change).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotation_batches', function (Blueprint $table) {
            $table->string('tank_type')->nullable()->after('client_id');
            $table->string('capacity')->nullable()->after('tank_type');
            $table->unsignedInteger('tank_quantity')->default(1)->after('capacity');
        });
    }

    public function down(): void
    {
        Schema::table('quotation_batches', function (Blueprint $table) {
            $table->dropColumn(['tank_type', 'capacity', 'tank_quantity']);
        });
    }
};
