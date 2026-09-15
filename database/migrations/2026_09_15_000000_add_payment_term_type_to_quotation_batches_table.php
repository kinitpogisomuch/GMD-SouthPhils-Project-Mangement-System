<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotation_batches', function (Blueprint $table) {
            // Chosen alongside Markup, before "Convert to Project" — lets conversion
            // auto-create the Payment record instead of a separate manual setup step.
            $table->string('payment_term_type')->nullable()->after('contract_value'); // big_project | small_project
        });
    }

    public function down(): void
    {
        Schema::table('quotation_batches', function (Blueprint $table) {
            $table->dropColumn('payment_term_type');
        });
    }
};
