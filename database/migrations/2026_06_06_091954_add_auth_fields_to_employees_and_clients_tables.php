<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('username')->nullable()->unique()->after('status');
            $table->string('password')->nullable()->after('username');
            $table->boolean('first_login')->default(false)->after('password');
            $table->timestamp('credentials_sent_at')->nullable()->after('first_login');
            $table->string('region')->nullable()->after('credentials_sent_at');
            $table->string('province')->nullable()->after('region');
            $table->string('city')->nullable()->after('province');
            $table->string('barangay')->nullable()->after('city');
            $table->string('street_address')->nullable()->after('barangay');
            $table->timestamp('password_updated_at')->nullable()->after('street_address');
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->string('username')->nullable()->unique()->after('status');
            $table->string('password')->nullable()->after('username');
            $table->boolean('first_login')->default(false)->after('password');
            $table->timestamp('credentials_sent_at')->nullable()->after('first_login');
            $table->string('region')->nullable()->after('credentials_sent_at');
            $table->string('province')->nullable()->after('region');
            $table->string('city')->nullable()->after('province');
            $table->string('barangay')->nullable()->after('city');
            $table->string('street_address')->nullable()->after('barangay');
            $table->timestamp('password_updated_at')->nullable()->after('street_address');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn([
                'username', 'password', 'first_login', 'credentials_sent_at',
                'region', 'province', 'city', 'barangay', 'street_address', 'password_updated_at',
            ]);
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn([
                'username', 'password', 'first_login', 'credentials_sent_at',
                'region', 'province', 'city', 'barangay', 'street_address', 'password_updated_at',
            ]);
        });
    }
};
