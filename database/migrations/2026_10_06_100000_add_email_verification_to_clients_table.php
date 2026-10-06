<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Email verification is its own status, separate from account approval:
 *   Email:   Unverified (email_verified_at null) → Verified (email_verified_at set)
 *   Account: Pending (Pending Approval) → Active (Approved) / Rejected
 *
 * Clients that already exist were created before this step (by the admin or through the old
 * sign-up), so they are marked verified — nobody already in the system gets locked out.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->timestamp('email_verified_at')->nullable();
            $table->string('email_verification_token', 64)->nullable();   // sha256 of the link token
            $table->timestamp('email_verification_sent_at')->nullable();
        });

        DB::table('clients')->whereNull('email_verified_at')->update([
            'email_verified_at' => DB::raw('COALESCE(created_at, NOW())'),
        ]);
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['email_verified_at', 'email_verification_token', 'email_verification_sent_at']);
        });
    }
};
