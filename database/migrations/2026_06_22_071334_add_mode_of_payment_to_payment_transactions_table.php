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
        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->string('mode_of_payment')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->dropColumn('mode_of_payment');
        });
    }
};
