<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProjectSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('projects')->insert([
            [
                'name'           => 'Fabrication of Fuel Day Tank',
                'client'         => 'Powercity - East service Road, Brgy. Cupang, Muntinlupa City',
                'client_type'    => 'Corporate',
                'tank_type'      => 'Fuel Day Tank',
                'capacity'       => '20,000 L',
                'start_date'     => '2026-05-10',
                'end_date'       => '2026-06-24',
                'payment_status' => 'Partial',
                'status'         => 'ongoing',
                'progress'       => 65,
                'duration'       => '45 days',
                'notes'          => 'Corporate approval required multiple review stages.',
                'created_at'     => now(),
                'updated_at'     => now(),
            ],
            [
                'name'           => 'Fabrication of Aboveground Water Storage Tanks',
                'client'         => 'Sun Valley Golf Club - Antipolo, Rizal',
                'client_type'    => 'Corporate',
                'tank_type'      => 'Aboveground Water Tank',
                'capacity'       => '10,000 L',
                'start_date'     => '2026-04-28',
                'end_date'       => '2026-05-12',
                'payment_status' => 'Paid',
                'status'         => 'completed',
                'progress'       => 100,
                'duration'       => '14 days',
                'notes'          => 'Small project, completed on schedule.',
                'created_at'     => now(),
                'updated_at'     => now(),
            ],
            [
                'name'           => 'Fabrication of Underground Fuel Storage Tanks',
                'client'         => 'Hyundai Construction - Sta. Rosa, Laguna',
                'client_type'    => 'Corporate',
                'tank_type'      => 'Underground Fuel Tank',
                'capacity'       => '5,000 L',
                'start_date'     => '2026-05-20',
                'end_date'       => '2026-07-05',
                'payment_status' => 'Pending',
                'status'         => 'pending',
                'progress'       => 15,
                'duration'       => '46 days',
                'notes'          => 'Awaiting down payment before mobilization.',
                'created_at'     => now(),
                'updated_at'     => now(),
            ],
        ]);
    }
}