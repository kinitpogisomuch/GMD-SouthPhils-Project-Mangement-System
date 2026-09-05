<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_statements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
            $table->string('attention')->nullable();
            $table->string('bill_to')->nullable();
            $table->date('statement_date');
            $table->string('reference_no')->nullable();
            $table->string('tin_number')->nullable();
            $table->string('project_title')->nullable();
            $table->text('project_location')->nullable();
            $table->string('po_number')->nullable();
            $table->string('pr_number')->nullable();
            $table->string('subject')->nullable();
            $table->text('deposit_instructions')->nullable();
            $table->string('prepared_by_name')->nullable();
            $table->string('prepared_by_role')->nullable();
            $table->string('approved_by_name')->nullable();
            $table->string('approved_by_role')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_statements');
    }
};
