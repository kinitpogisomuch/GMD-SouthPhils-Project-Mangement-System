<?php

namespace Database\Seeders;

use App\Models\SupplierContact;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = [
            ['name' => 'Steeltrust Corporation', 'company' => 'Steeltrust Corporation', 'address' => 'Quezon City',     'phone' => '9175324562', 'email' => 'steeltrustcorporation@yahoo.com'],
            ['name' => 'J & B Steel',            'company' => 'J & B Steel',            'address' => 'Calamba, Laguna', 'phone' => '9209503298', 'email' => null],
            ['name' => 'EMZ 7 Hardware',         'company' => 'EMZ 7 Hardware',         'address' => 'Calauan, Laguna', 'phone' => '9662994326', 'email' => null],
            ['name' => 'Nine Golden Hardware',   'company' => 'Nine Golden Hardware',   'address' => 'Calauan, Laguna', 'phone' => '9945349196', 'email' => null],
            ['name' => 'New Alfred Hardware',    'company' => 'New Alfred Hardware',    'address' => 'Binondo, Manila', 'phone' => '9270019360', 'email' => null],
            ['name' => 'An Yiac Hardware',       'company' => 'An Yiac Hardware',       'address' => 'Binondo, Manila', 'phone' => '287335775',  'email' => null],
            ['name' => 'Symbolic Trading',       'company' => 'Symbolic Trading',       'address' => 'Binondo, Manila', 'phone' => '9773639568', 'email' => null],
            ['name' => 'Lanfeng Fuel Pump',      'company' => 'Lanfeng Fuel Pump',      'address' => 'Bulacan',         'phone' => '9776805538', 'email' => null],
            ['name' => 'Leared Enterprises',     'company' => 'Leared Enterprises',     'address' => 'Valenzuela City', 'phone' => '9175894501', 'email' => null],
        ];

        foreach ($suppliers as $s) {
            SupplierContact::firstOrCreate(['name' => $s['name']], $s);
        }
    }
}
