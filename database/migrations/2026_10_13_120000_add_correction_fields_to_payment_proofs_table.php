<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Proof-of-payment corrections: the admin can return a submission with a reason
 * (status "needs_correction"), and the client resubmits the SAME proof — corrected amount,
 * mode or file — instead of creating a new one. Each earlier version is kept in `revisions`
 * so the review history stays on record.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_proofs', function (Blueprint $table) {
            $table->text('correction_reason')->nullable()->after('status');
            $table->timestamp('returned_at')->nullable()->after('correction_reason');
            $table->timestamp('resubmitted_at')->nullable()->after('returned_at');
            $table->json('revisions')->nullable()->after('resubmitted_at');
        });
    }

    public function down(): void
    {
        Schema::table('payment_proofs', function (Blueprint $table) {
            $table->dropColumn(['correction_reason', 'returned_at', 'resubmitted_at', 'revisions']);
        });
    }
};
