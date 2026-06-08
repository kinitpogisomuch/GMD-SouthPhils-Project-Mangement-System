<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('users')->insert([
            [
                'full_name' => 'Admin',
                'username'  => 'admin',
                'email'     => 'admin@gmd.com',
                'password'  => Hash::make('admin'),
                'role'      => 'admin',
                'position'  => 'Administrator',
                'status'    => 'Active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'full_name' => 'Client',
                'username'  => 'client',
                'email'     => 'client@gmd.com',
                'password'  => Hash::make('client'),
                'role'      => 'client',
                'position'  => 'Client',
                'status'    => 'Active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'full_name' => 'Employee',
                'username'  => 'employee',
                'email'     => 'employee@gmd.com',
                'password'  => Hash::make('employee'),
                'role'      => 'employee',
                'position'  => 'Employee',
                'status'    => 'Active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}