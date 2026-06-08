<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('contact_number')->nullable()->after('client');
            $table->string('email')->nullable()->after('contact_number');
            $table->string('address')->nullable()->after('email');
            $table->string('dimensions')->nullable()->after('capacity');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['contact_number', 'email', 'address', 'dimensions']);
        });
    }
};