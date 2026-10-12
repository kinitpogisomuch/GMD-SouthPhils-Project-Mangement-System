<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Duplicate protection:
     *  - notifications.event_key: one notification per recipient per event (e.g. "payment_due:12:down_payment").
     *    Notifications without a key behave exactly as before.
     *  - submission_tokens: each form submit carries a one-time token; a repeat (double click, refresh,
     *    simultaneous request) with the same token is refused.
     *  - an OR / Serial Number can only be used once (it was only checked in code; now the database enforces it too).
     */
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->string('event_key', 150)->nullable();
        });
        DB::statement('CREATE UNIQUE INDEX notifications_recipient_event_unique ON notifications (user_type, user_id, event_key) WHERE event_key IS NOT NULL');

        Schema::create('submission_tokens', function (Blueprint $table) {
            $table->string('token', 64)->primary();
            $table->string('path', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        DB::statement("CREATE UNIQUE INDEX payment_transactions_reference_number_unique ON payment_transactions (LOWER(TRIM(reference_number))) WHERE reference_number IS NOT NULL AND TRIM(reference_number) <> ''");
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS payment_transactions_reference_number_unique');
        Schema::dropIfExists('submission_tokens');
        DB::statement('DROP INDEX IF EXISTS notifications_recipient_event_unique');
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropColumn('event_key');
        });
    }
};
