<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('monthly_expenses', function (Blueprint $table) {
            $table->id();
            $table->string('month_year', 7);          // e.g. "2026-07"
            $table->string('category');               // Electricity, Water, Rent, etc.
            $table->string('description')->nullable();
            $table->decimal('amount', 14, 2);
            $table->timestamps();
            $table->index('month_year');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monthly_expenses');
    }
};
