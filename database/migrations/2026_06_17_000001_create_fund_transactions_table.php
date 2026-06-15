<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fund_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // 'deposit' (replenishment) | 'withdrawal' (expense)
            $table->decimal('amount', 15, 2);
            $table->date('date');
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->text('description');
            $table->string('recorded_by')->nullable();
            $table->timestamps();

            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fund_transactions');
    }
};
