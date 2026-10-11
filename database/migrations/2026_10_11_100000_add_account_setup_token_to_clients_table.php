<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Self-registered clients set their own password after the admin approves them, through a
     * one-time "Set Up Account" link emailed on approval. Only the token's SHA-256 hash is stored;
     * account_setup_sent_at makes the link time-limited.
     *
     * Registration lifecycle (status column + email_verified_at, no new status column):
     *   Pending + unverified email   → Pending Email Verification
     *   Pending + verified email     → Pending Admin Approval
     *   Approved                     → Approved – Account Setup Required
     *   Active                       → Active
     *   Rejected                     → Rejected
     */
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('account_setup_token')->nullable()->after('email_verification_sent_at');
            $table->timestamp('account_setup_sent_at')->nullable()->after('account_setup_token');
            // one registration per email, enforced by the database too (NULL emails are still allowed
            // for admin-created clients without one); usernames are already unique
            $table->unique('email');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropUnique(['email']);
            $table->dropColumn(['account_setup_token', 'account_setup_sent_at']);
        });
    }
};
