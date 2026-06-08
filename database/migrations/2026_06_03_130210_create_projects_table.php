<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('client');
            $table->enum('client_type', ['Corporate', 'Individual Owner']);
            $table->string('tank_type');
            $table->string('capacity');
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('payment_status', ['Pending', 'Partial', 'Paid'])->default('Pending');
            $table->enum('status', ['pending', 'ongoing', 'completed'])->default('pending');
            $table->integer('progress')->default(0);
            $table->string('duration')->nullable();
            $table->text('notes')->nullable();
            
            // NEW FIELDS FOR PHASE MANAGEMENT
            $table->enum('current_phase', [
                'planning',
                'procurement',
                'matl_prep',
                'fabrication',
                'inspection',
                'painting',
                'completion',
                'delivery'
            ])->default('planning');
            
            $table->integer('total_phases')->default(8); // Total number of phases
            $table->integer('completion_percentage')->default(0);
            $table->json('phase_completion_status')->nullable(); // Store which phases are completed
            
            // NEW FIELD FOR TRACKING LAST PHASE UPDATE
            $table->timestamp('last_phase_update_at')->nullable();
            $table->unsignedBigInteger('last_phase_updated_by')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index('current_phase');
            $table->index('status');
            $table->index('completion_percentage');
            
            // Foreign key for last_phase_updated_by
            $table->foreign('last_phase_updated_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};