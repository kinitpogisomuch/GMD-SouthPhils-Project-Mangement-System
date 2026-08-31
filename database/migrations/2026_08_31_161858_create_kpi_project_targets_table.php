<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kpi_project_targets', function (Blueprint $table) {
            $table->id();
            $table->decimal('min_profit_per_project', 14, 2)->default(0); // pesos
            $table->unsignedInteger('max_duration_days')->default(0);
            $table->decimal('budget_adherence_target', 5, 2)->default(100); // percent
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kpi_project_targets');
    }
};
