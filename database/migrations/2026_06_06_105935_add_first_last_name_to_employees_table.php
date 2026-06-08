<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add nullable first
        Schema::table('employees', function (Blueprint $table) {
            $table->string('first_name')->nullable()->after('id');
            $table->string('last_name')->nullable()->after('first_name');
        });

        // Migrate names stored as "LastName, FirstName" → split on ", "
        DB::statement("UPDATE employees SET first_name = TRIM(SPLIT_PART(name, ', ', 2)), last_name = TRIM(SPLIT_PART(name, ', ', 1)) WHERE name IS NOT NULL AND name != '' AND name LIKE '%, %'");

        // Fallback for names without comma → first word = first_name, rest = last_name
        DB::statement("UPDATE employees SET first_name = TRIM(SPLIT_PART(name, ' ', 1)), last_name = TRIM(SUBSTRING(name FROM POSITION(' ' IN name) + 1)) WHERE (first_name IS NULL OR first_name = '') AND name IS NOT NULL AND name != ''");

        // Make NOT NULL
        Schema::table('employees', function (Blueprint $table) {
            $table->string('first_name')->nullable(false)->change();
            $table->string('last_name')->nullable(false)->change();
        });

        // Drop the old column
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('name');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('name')->nullable()->after('id');
        });

        DB::statement("
            UPDATE employees SET name = TRIM(first_name || ' ' || last_name)
        ");

        Schema::table('employees', function (Blueprint $table) {
            $table->string('name')->nullable(false)->change();
            $table->dropColumn(['first_name', 'last_name']);
        });
    }
};
