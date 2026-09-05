<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->text('rejection_reason')->nullable();
        });

        if (DB::getDriverName() !== 'pgsql') return;
        DB::statement('ALTER TABLE clients DROP CONSTRAINT IF EXISTS clients_status_check');
        DB::statement("ALTER TABLE clients ADD CONSTRAINT clients_status_check CHECK (status IN ('Active', 'Inactive', 'Pending', 'Rejected'))");
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE clients DROP CONSTRAINT IF EXISTS clients_status_check');
            DB::statement("ALTER TABLE clients ADD CONSTRAINT clients_status_check CHECK (status IN ('Active', 'Inactive'))");
        }

        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('rejection_reason');
        });
    }
};
