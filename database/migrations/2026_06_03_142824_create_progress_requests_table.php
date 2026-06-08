<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('progress_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('requested_by');
            $table->text('message')->nullable();
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
            $table->enum('status', ['open', 'completed', 'revision_requested'])->default('open');
            $table->unsignedBigInteger('fulfilled_by')->nullable();
            $table->timestamp('fulfilled_at')->nullable();
            
            // NEW FIELDS FOR INTEGRATION WITH APPROVAL WORKFLOW
            $table->unsignedBigInteger('project_update_id')->nullable(); // Link to the submitted update
            $table->text('revision_feedback')->nullable(); // Store revision feedback
            $table->timestamp('revision_requested_at')->nullable();
            $table->unsignedBigInteger('revision_requested_by')->nullable();
            
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
            $table->foreign('requested_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('fulfilled_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('project_update_id')->references('id')->on('project_updates')->onDelete('set null');
            $table->foreign('revision_requested_by')->references('id')->on('users')->onDelete('set null');
            
            // Indexes
            $table->index(['project_id', 'status']);
            $table->index('phase');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('progress_requests');
    }
};