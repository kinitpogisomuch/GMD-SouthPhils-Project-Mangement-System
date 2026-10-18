<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tank cards the admin adds in Settings (besides the built-in tank types): their own name and capacity
     * label. Clients request them with a general form (capacity, description, dimensions).
     */
    public function up(): void
    {
        Schema::table('tank_cards', function (Blueprint $table) {
            $table->boolean('is_custom')->default(false);
            $table->string('type', 100)->nullable();
            $table->string('capacity_label', 50)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('tank_cards', function (Blueprint $table) {
            $table->dropColumn(['is_custom', 'type', 'capacity_label']);
        });
    }
};
