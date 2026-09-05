<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotation_requests', function (Blueprint $table) {
            $table->json('tank_items')->nullable()->after('client_id');
            $table->dropColumn(['tank_type', 'capacity', 'quantity']);
        });
    }

    public function down(): void
    {
        Schema::table('quotation_requests', function (Blueprint $table) {
            $table->string('tank_type')->nullable()->after('client_id');
            $table->string('capacity')->nullable()->after('tank_type');
            $table->unsignedInteger('quantity')->default(1)->after('capacity');
            $table->dropColumn('tank_items');
        });
    }
};
