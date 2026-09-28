<?php

use App\Models\PaymentProof;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_proofs', function (Blueprint $table) {
            // Whether admin has acted on THIS specific submission — independent of whether the
            // stage as a whole has been fully paid (a stage can need several partial payments,
            // each with its own proof, before the cumulative total settles the stage itself).
            $table->string('status')->default('pending')->after('mode_of_payment'); // pending | confirmed
        });

        // Backfill: a proof whose own stage was already settled before this column existed was
        // clearly dealt with one way or another, so mark it confirmed rather than newly pending.
        PaymentProof::with('payment')->get()->each(function (PaymentProof $proof) {
            if ($proof->payment && in_array($proof->payment_stage, $proof->payment->paidStages(), true)) {
                $proof->update(['status' => 'confirmed']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('payment_proofs', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
