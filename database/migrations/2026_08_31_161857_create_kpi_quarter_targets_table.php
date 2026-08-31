<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kpi_quarter_targets', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('quarter'); // 1-4
            $table->decimal('profit_target', 14, 2)->default(0);       // pesos
            $table->unsignedInteger('on_time_target')->default(0);     // number of projects
            $table->decimal('budget_adherence_target', 5, 2)->default(100); // percent
            $table->timestamps();

            $table->unique(['year', 'quarter']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kpi_quarter_targets');
    }
};
