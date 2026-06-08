<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('material_requests', function (Blueprint $table) {
            $table->id();
            $table->string('material');
            $table->integer('quantity');
            $table->string('unit');
            $table->string('project');
            $table->string('supplier');
            $table->date('requested_date');
            $table->enum('status', ['pending', 'fulfilled', 'shortage'])->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('material_requests');
    }
};