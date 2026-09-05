<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotation_requests', function (Blueprint $table) {
            $table->json('quotation_files')->nullable()->after('notes');
            $table->timestamp('quotation_sent_at')->nullable()->after('status');
            $table->timestamp('approved_at')->nullable()->after('quotation_sent_at');
        });

        if (DB::getDriverName() !== 'pgsql') return;
        DB::statement('ALTER TABLE quotation_requests DROP CONSTRAINT IF EXISTS quotation_requests_status_check');
        DB::statement("ALTER TABLE quotation_requests ADD CONSTRAINT quotation_requests_status_check CHECK (status IN ('pending', 'quotation_sent', 'approved', 'converted', 'declined'))");
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE quotation_requests DROP CONSTRAINT IF EXISTS quotation_requests_status_check');
            DB::statement("ALTER TABLE quotation_requests ADD CONSTRAINT quotation_requests_status_check CHECK (status IN ('pending', 'converted', 'declined'))");
        }

        Schema::table('quotation_requests', function (Blueprint $table) {
            $table->dropColumn(['quotation_files', 'quotation_sent_at', 'approved_at']);
        });
    }
};
