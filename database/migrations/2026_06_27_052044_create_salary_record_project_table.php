<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_record_project', function (Blueprint $table) {
            $table->unsignedBigInteger('salary_record_id');
            $table->unsignedBigInteger('project_id');
            $table->primary(['salary_record_id','project_id']);
            $table->foreign('salary_record_id')->references('id')->on('salary_records')->cascadeOnDelete();
            $table->foreign('project_id')->references('id')->on('projects')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_record_project');
    }
};
