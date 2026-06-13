<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('material_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('project_material_id')->nullable()->constrained('project_materials')->nullOnDelete();
            $table->string('material_name');
            $table->decimal('quantity_used', 10, 2);
            $table->string('unit')->nullable();
            $table->date('used_date');
            $table->string('used_for')->nullable();
            $table->text('notes')->nullable();
            $table->string('recorded_by')->nullable();
            $table->string('status')->default('active'); // active, archived
            $table->timestamps();

            $table->index(['project_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('material_usages');
    }
};
