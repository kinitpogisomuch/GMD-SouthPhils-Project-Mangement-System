<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_labor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('description');
            $table->decimal('hours', 8, 2);
            $table->decimal('rate_per_hour', 14, 2);
            $table->decimal('total_cost', 16, 2);
            $table->text('notes')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
            $table->index(['project_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_labor');
    }
};
