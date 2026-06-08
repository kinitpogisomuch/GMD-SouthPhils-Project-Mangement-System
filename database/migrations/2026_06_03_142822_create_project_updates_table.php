<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_updates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('submitted_by')->nullable(); // Made nullable for system updates
            $table->enum('type', ['admin_direct', 'employee_submission', 'requested', 'system', 'regular'])->default('regular');
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
            $table->integer('percentage')->default(0);
            $table->date('date_of_work')->nullable(); // Made nullable
            $table->text('work_done');
            $table->text('issues')->nullable();
            $table->json('photos')->nullable();
            
            // NEW FIELDS FOR APPROVAL WORKFLOW
            $table->enum('approval_status', ['pending', 'approved', 'revision_required'])->default('approved');
            $table->text('revision_feedback')->nullable();
            $table->unsignedBigInteger('requested_by_admin_id')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('approved_by_admin_id')->nullable();
            $table->enum('update_type', ['regular', 'requested', 'system'])->default('regular');
            
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
            $table->foreign('submitted_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('requested_by_admin_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('approved_by_admin_id')->references('id')->on('users')->onDelete('set null');
            
            // Indexes for better performance
            $table->index(['project_id', 'approval_status']);
            $table->index(['project_id', 'update_type']);
            $table->index('submitted_by');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_updates');
    }
};