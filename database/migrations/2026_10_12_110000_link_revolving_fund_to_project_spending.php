<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * When a purchase, salary allocation or overhead allocation costs more than a project has received,
     * the revolving fund covers the difference. These columns record what each fund entry was for, and
     * which purchases a release paid for (so deleting such a purchase can return the money to the fund).
     */
    public function up(): void
    {
        Schema::table('fund_transactions', function (Blueprint $table) {
            $table->string('source_type', 30)->nullable();   // material_purchase / labor / overhead / purchase_deleted
            $table->string('source_ref', 100)->nullable();   // e.g. salary record id, "2026-10" for overhead
        });

        Schema::table('material_purchases', function (Blueprint $table) {
            $table->unsignedBigInteger('fund_transaction_id')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('material_purchases', function (Blueprint $table) {
            $table->dropColumn('fund_transaction_id');
        });
        Schema::table('fund_transactions', function (Blueprint $table) {
            $table->dropColumn(['source_type', 'source_ref']);
        });
    }
};
