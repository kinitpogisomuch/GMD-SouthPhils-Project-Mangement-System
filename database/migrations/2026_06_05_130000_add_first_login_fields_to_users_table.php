<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('first_login')->default(false)->after('status');
            $table->string('region')->nullable()->after('first_login');
            $table->string('province')->nullable()->after('region');
            $table->string('city')->nullable()->after('province');
            $table->string('barangay')->nullable()->after('city');
            $table->string('street_address')->nullable()->after('barangay');
            $table->timestamp('password_updated_at')->nullable()->after('street_address');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'first_login', 'region', 'province', 'city',
                'barangay', 'street_address', 'password_updated_at',
            ]);
        });
    }
};
