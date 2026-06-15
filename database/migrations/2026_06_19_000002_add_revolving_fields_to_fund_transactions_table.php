<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fund_transactions', function (Blueprint $table) {
            $table->string('purpose')->nullable()->after('amount');
            $table->text('remarks')->nullable()->after('description');
            $table->string('status')->default('completed')->after('remarks');
            $table->decimal('balance_after', 15, 2)->nullable()->after('status');
        });

        // Map the old deposit/withdrawal ledger onto the new release/replenishment model.
        DB::table('fund_transactions')->where('type', 'withdrawal')->update([
            'type'   => 'release',
            'status' => 'Pending Replenishment',
        ]);

        DB::table('fund_transactions')->where('type', 'deposit')->update([
            'type'   => 'replenishment',
            'status' => 'Completed',
        ]);
    }

    public function down(): void
    {
        DB::table('fund_transactions')->where('type', 'release')->update(['type' => 'withdrawal']);
        DB::table('fund_transactions')->where('type', 'replenishment')->update(['type' => 'deposit']);

        Schema::table('fund_transactions', function (Blueprint $table) {
            $table->dropColumn(['purpose', 'remarks', 'status', 'balance_after']);
        });
    }
};
