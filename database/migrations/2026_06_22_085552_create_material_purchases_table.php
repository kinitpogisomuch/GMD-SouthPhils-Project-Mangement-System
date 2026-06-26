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
        Schema::create('material_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('project_material_id')->nullable()->constrained('project_materials')->nullOnDelete();
            $table->string('material_name');
            $table->string('unit')->nullable();
            $table->decimal('qty_bought', 12, 2)->default(1);
            $table->decimal('actual_unit_cost', 12, 2)->default(0);
            $table->decimal('total_paid', 12, 2)->default(0);
            $table->string('supplier')->nullable();
            $table->date('purchase_date');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('material_purchases');
    }
};
