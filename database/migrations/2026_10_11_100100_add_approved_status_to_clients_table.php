<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * "Approved" = Approved – Account Setup Required: the admin approved the registration and the
     * client has been emailed a Set Up Account link, but hasn't created their password yet.
     */
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') return;
        DB::statement('ALTER TABLE clients DROP CONSTRAINT IF EXISTS clients_status_check');
        DB::statement("ALTER TABLE clients ADD CONSTRAINT clients_status_check CHECK (status IN ('Active', 'Inactive', 'Pending', 'Approved', 'Rejected'))");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') return;
        // approved-but-not-set-up accounts go back to awaiting approval
        DB::table('clients')->where('status', 'Approved')->update(['status' => 'Pending']);
        DB::statement('ALTER TABLE clients DROP CONSTRAINT IF EXISTS clients_status_check');
        DB::statement("ALTER TABLE clients ADD CONSTRAINT clients_status_check CHECK (status IN ('Active', 'Inactive', 'Pending', 'Rejected'))");
    }
};
