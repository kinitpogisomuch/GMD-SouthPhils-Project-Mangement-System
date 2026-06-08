<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('contact')->nullable();
            $table->enum('role', ['Fabricator', 'Welder', 'Helper/Labor', 'Outsourced']);
            $table->decimal('daily_rate', 10, 2)->default(0);
            $table->enum('pay_type', ['Daily', 'Weekly', 'Monthly'])->default('Daily');
            $table->decimal('sss', 10, 2)->default(0);
            $table->decimal('philhealth', 10, 2)->default(0);
            $table->decimal('pagibig', 10, 2)->default(0);
            $table->decimal('other_deductions', 10, 2)->default(0);
            $table->date('date_hired')->nullable();
            $table->enum('status', ['Active', 'Inactive'])->default('Active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};