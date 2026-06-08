<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MaterialRequestSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('material_requests')->insert([
            [
                'material'       => 'Welding Electrodes (6013)',
                'quantity'       => 50,
                'unit'           => 'kg',
                'project'        => 'Fuel Storage Tank Fabrication',
                'supplier'       => 'Steel Supply Co.',
                'requested_date' => '2026-05-12',
                'status'         => 'pending',
                'created_at'     => now(),
                'updated_at'     => now(),
            ],
            [
                'material'       => 'Steel Plates (6mm)',
                'quantity'       => 20,
                'unit'           => 'sheets',
                'project'        => 'Fuel Storage Tank Fabrication',
                'supplier'       => 'MetalWorks PH',
                'requested_date' => '2026-05-10',
                'status'         => 'fulfilled',
                'created_at'     => now(),
                'updated_at'     => now(),
            ],
            [
                'material'       => 'Grinding Discs',
                'quantity'       => 30,
                'unit'           => 'pcs',
                'project'        => 'Chemical Tank Repair',
                'supplier'       => 'Steel Supply Co.',
                'requested_date' => '2026-05-18',
                'status'         => 'pending',
                'created_at'     => now(),
                'updated_at'     => now(),
            ],
            [
                'material'       => 'Welding Rods (7018)',
                'quantity'       => 25,
                'unit'           => 'kg',
                'project'        => 'Fuel Storage Tank Fabrication',
                'supplier'       => 'MetalWorks PH',
                'requested_date' => '2026-05-08',
                'status'         => 'shortage',
                'created_at'     => now(),
                'updated_at'     => now(),
            ],
            [
                'material'       => 'Angle Bars (2x2)',
                'quantity'       => 40,
                'unit'           => 'pcs',
                'project'        => 'Chemical Tank Repair',
                'supplier'       => 'Steel Supply Co.',
                'requested_date' => '2026-05-05',
                'status'         => 'fulfilled',
                'created_at'     => now(),
                'updated_at'     => now(),
            ],
            [
                'material'       => 'Safety Gloves',
                'quantity'       => 10,
                'unit'           => 'pairs',
                'project'        => 'All Projects',
                'supplier'       => 'Safety Depot PH',
                'requested_date' => '2026-05-15',
                'status'         => 'shortage',
                'created_at'     => now(),
                'updated_at'     => now(),
            ],
        ]);
    }
}