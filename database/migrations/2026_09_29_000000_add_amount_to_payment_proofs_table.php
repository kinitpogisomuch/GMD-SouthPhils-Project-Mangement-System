<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_proofs', function (Blueprint $table) {
            // The amount the client says they paid for this stage — shown alongside the
            // uploaded file/image and the stage it belongs to on the client Payments page.
            $table->decimal('amount', 15, 2)->nullable()->after('payment_stage');
        });
    }

    public function down(): void
    {
        Schema::table('payment_proofs', function (Blueprint $table) {
            $table->dropColumn('amount');
        });
    }
};
