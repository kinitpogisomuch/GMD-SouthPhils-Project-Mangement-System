<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('performance_test_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('client_name')->nullable();
            $table->text('project_location')->nullable();
            $table->string('subject')->nullable();
            $table->date('report_date');
            $table->json('test_items');          // [{item, tank_capacity, applied_pressure, tested_at, hours_observed, remarks}]
            $table->json('test_photos')->nullable(); // "Actual Test" jobsite photos
            $table->string('conducted_by_name')->nullable();
            $table->string('conducted_by_role')->nullable();
            $table->string('noted_by_name')->nullable();
            $table->string('noted_by_role')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_test_reports');
    }
};
