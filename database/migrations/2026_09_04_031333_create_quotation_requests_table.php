<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotation_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->string('tank_type');
            $table->string('capacity')->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->string('target_timeline')->nullable();
            $table->text('location');
            $table->text('notes')->nullable();
            $table->string('status')->default('pending');
            $table->foreignId('related_project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->text('decline_reason')->nullable();
            $table->timestamps();

            $table->index('status');
        });

        if (DB::getDriverName() !== 'pgsql') return;
        DB::statement("ALTER TABLE quotation_requests ADD CONSTRAINT quotation_requests_status_check CHECK (status IN ('pending', 'converted', 'declined'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('quotation_requests');
    }
};
