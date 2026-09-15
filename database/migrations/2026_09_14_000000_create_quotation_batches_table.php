<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotation_batches', function (Blueprint $table) {
            // Shares its id with quotation_requests.batch_id (the uuid already
            // generated when a client submits a request) rather than minting a
            // second identifier for the same grouping.
            $table->uuid('id')->primary();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->unsignedInteger('estimated_working_days')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotation_batches');
    }
};
