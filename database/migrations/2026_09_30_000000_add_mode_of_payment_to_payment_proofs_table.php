<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_proofs', function (Blueprint $table) {
            // How the client says they paid — shown alongside the amount and file on the
            // client Payments page and in the admin "Client Submissions" list.
            $table->string('mode_of_payment')->nullable()->after('amount');
        });
    }

    public function down(): void
    {
        Schema::table('payment_proofs', function (Blueprint $table) {
            $table->dropColumn('mode_of_payment');
        });
    }
};
