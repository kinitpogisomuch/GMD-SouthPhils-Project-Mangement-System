<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('user_type');           // admin, employee, client
            $table->string('title');
            $table->text('message');
            $table->unsignedBigInteger('related_project_id')->nullable();
            $table->unsignedBigInteger('related_progress_id')->nullable();
            $table->string('notification_type');   // project_created, progress_requested, etc.
            $table->string('priority')->default('info'); // info, warning, success
            $table->string('action_url')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['user_id', 'is_read']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
