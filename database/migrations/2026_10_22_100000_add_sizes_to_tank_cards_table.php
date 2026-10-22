<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The standard sizes a tank card offers on its details page (capacity + dimensions), as edited in Settings.
     * Null = the catalog's default sizes; an empty list = no standard sizes (the client types a capacity).
     */
    public function up(): void
    {
        Schema::table('tank_cards', function (Blueprint $table) {
            $table->json('sizes')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('tank_cards', function (Blueprint $table) {
            $table->dropColumn('sizes');
        });
    }
};
