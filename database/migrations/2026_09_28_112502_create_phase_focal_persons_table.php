<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phase_focal_persons', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id');
            $table->enum('phase', [
                'planning',
                'procurement',
                'matl_prep',
                'fabrication',
                'inspection',
                'painting',
                'completion',
                'delivery'
            ]);
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('assigned_by')->nullable();
            $table->timestamps();

            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('assigned_by')->references('id')->on('users')->onDelete('set null');

            $table->unique(['project_id', 'phase']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phase_focal_persons');
    }
};
