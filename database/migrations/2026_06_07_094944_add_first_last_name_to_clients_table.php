<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('first_name')->nullable()->after('id');
            $table->string('last_name')->nullable()->after('first_name');
        });

        // Backfill: split existing name into first_name / last_name
        // "Juan Dela Cruz" → first_name=Juan, last_name=Dela Cruz
        DB::table('clients')->get()->each(function ($client) {
            if ($client->name) {
                $parts     = explode(' ', trim($client->name), 2);
                $firstName = $parts[0] ?? '';
                $lastName  = isset($parts[1]) ? $parts[1] : ($parts[0] ?? '');
                DB::table('clients')->where('id', $client->id)->update([
                    'first_name' => $firstName,
                    'last_name'  => $lastName,
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['first_name', 'last_name']);
        });
    }
};
