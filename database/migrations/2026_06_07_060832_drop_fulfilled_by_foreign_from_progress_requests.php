<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // fulfilled_by holds an employees.id, not users.id — drop the wrong FK
        Schema::table('progress_requests', function (Blueprint $table) {
            $table->dropForeign('progress_requests_fulfilled_by_foreign');
        });
    }

    public function down(): void
    {
        Schema::table('progress_requests', function (Blueprint $table) {
            $table->foreign('fulfilled_by')->references('id')->on('users')->onDelete('set null');
        });
    }
};
