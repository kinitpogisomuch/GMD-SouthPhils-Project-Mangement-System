<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->onDelete('cascade');
            $table->string('material_name');
            $table->string('category')->nullable();
            $table->decimal('quantity', 10, 2);
            $table->string('unit');
            $table->decimal('price_per_unit', 14, 2);
            $table->decimal('total_cost', 16, 2);
            $table->text('notes')->nullable();
            $table->string('status')->default('active'); // active, archived
            $table->timestamps();

            $table->index(['project_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_materials');
    }
};
