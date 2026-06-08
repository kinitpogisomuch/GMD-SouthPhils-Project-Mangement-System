<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $workers = [
            [
                'first_name'       => 'Andres',
                'last_name'        => 'Cabanban',
                'role'             => 'Welder',
                'employee_type'    => 'Regular',
                'daily_rate'       => 700.00,
                'sss'              => 460.00,
                'philhealth'       => 250.00,
                'pagibig'          => 250.00,
                'other_deductions' => 0.00,
                'status'           => 'Active',
            ],
            [
                'first_name'       => 'Terence',
                'last_name'        => 'Almanza',
                'role'             => 'Fabricator',
                'employee_type'    => 'Regular',
                'daily_rate'       => 450.00,
                'sss'              => 400.00,
                'philhealth'       => 250.00,
                'pagibig'          => 250.00,
                'other_deductions' => 0.00,
                'status'           => 'Active',
            ],
            [
                'first_name'       => 'Robert',
                'last_name'        => 'Pineda',
                'role'             => 'Welder',
                'employee_type'    => 'Regular',
                'daily_rate'       => 550.00,
                'sss'              => 400.00,
                'philhealth'       => 250.00,
                'pagibig'          => 250.00,
                'other_deductions' => 0.00,
                'status'           => 'Active',
            ],
            [
                'first_name'       => 'Robert',
                'last_name'        => 'Olan',
                'role'             => 'Helper/Labor',
                'employee_type'    => 'Regular',
                'daily_rate'       => 400.00,
                'sss'              => 0.00,
                'philhealth'       => 250.00,
                'pagibig'          => 250.00,
                'other_deductions' => 0.00,
                'status'           => 'Active',
            ],
            [
                'first_name'       => 'Sammy',
                'last_name'        => 'Maceda',
                'role'             => 'Helper/Labor',
                'employee_type'    => 'Regular',
                'daily_rate'       => 400.00,
                'sss'              => 0.00,
                'philhealth'       => 250.00,
                'pagibig'          => 250.00,
                'other_deductions' => 0.00,
                'status'           => 'Active',
            ],
        ];

        foreach ($workers as $worker) {
            DB::table('employees')->updateOrInsert(
                ['first_name' => $worker['first_name'], 'last_name' => $worker['last_name']],
                array_merge($worker, ['created_at' => now(), 'updated_at' => now()])
            );
        }
    }
}
