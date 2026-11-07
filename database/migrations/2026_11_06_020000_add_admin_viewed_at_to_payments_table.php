<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // When an admin last opened this payment — a client payment made after it shows as "New Payment"
        // on the admin Payments list until an admin opens the payment again.
        Schema::table('payments', function (Blueprint $table) {
            $table->timestamp('admin_viewed_at')->nullable();
        });

        // Everything already on record counts as seen, so the list doesn't light up with old payments.
        DB::table('payments')->update(['admin_viewed_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('admin_viewed_at');
        });
    }
};
