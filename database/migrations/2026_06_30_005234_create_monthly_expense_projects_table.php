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
        Schema::create('monthly_expense_projects', function (Blueprint $table) {
            $table->id();
            $table->string('month_year', 7);
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->decimal('allocated_amount', 14, 2)->default(0);
            $table->timestamps();
            $table->unique(['month_year', 'project_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monthly_expense_projects');
    }
};
