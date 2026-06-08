<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Client;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Only seed clients if table is empty
        if (Client::count() === 0) {
            Client::insert([
                ['name' => 'Powercity',            'address' => 'East Service Road, Brgy. Cupang, Muntinlupa City', 'contact' => '0917 123 4567', 'email' => 'powercity@gmd.com',            'status' => 'Active', 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'Alvin Magtibay',       'address' => 'Lucena City',                                      'contact' => '0918 234 5678', 'email' => 'alvin.magtibay@gmail.com',    'status' => 'Active', 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'New JDT Trading',      'address' => 'Puerto Princesa, Palawan',                         'contact' => '0919 345 6789', 'email' => 'newjdt@gmail.com',             'status' => 'Active', 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'Innovative Agro',      'address' => 'Muntinlupa City',                                  'contact' => '0920 456 7890', 'email' => 'innovativeagro@gmail.com',     'status' => 'Active', 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'Hyundai Construction', 'address' => 'Sta. Rosa, Laguna',                                'contact' => '0921 567 8901', 'email' => 'hyundaiconstruction@gmail.com','status' => 'Active', 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'Sun Valley Golf Club', 'address' => 'Antipolo, Rizal',                                  'contact' => '0922 678 9012', 'email' => 'sunvalleygc@gmail.com',        'status' => 'Active', 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'RVL Movers',           'address' => 'Muntinlupa City',                                  'contact' => '0923 789 0123', 'email' => 'rvlmovers@gmail.com',          'status' => 'Active', 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'Mario Moncada',        'address' => 'Cavite City',                                      'contact' => '0924 890 1234', 'email' => 'mario.moncada@gmail.com',      'status' => 'Active', 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'Word of Life',         'address' => 'Calauan, Laguna',                                  'contact' => '0925 901 2345', 'email' => 'wordoflife@gmail.com',         'status' => 'Active', 'created_at' => now(), 'updated_at' => now()],
            ]);
        }
    }
}