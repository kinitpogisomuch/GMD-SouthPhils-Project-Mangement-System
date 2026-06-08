<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PaymentSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('payments')->insert([
            [
                'project_id'      => 1,
                'client'          => 'ABC Corporation',
                'client_type'     => 'Corporate',
                'contract_amount' => 850000,
                'down_payment'    => 425000,
                'balance'         => 425000,
                'status'          => 'Partial',
                'payment_terms'   => '2 phases',
                'date'            => '2026-05-10',
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            [
                'project_id'      => 2,
                'client'          => 'South Plant Inc.',
                'client_type'     => 'Owner',
                'contract_amount' => 280000,
                'down_payment'    => 140000,
                'balance'         => 0,
                'status'          => 'Paid',
                'payment_terms'   => '2 phases',
                'date'            => '2026-04-28',
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            [
                'project_id'      => 3,
                'client'          => 'Prime Chemicals',
                'client_type'     => 'Corporate',
                'contract_amount' => 420000,
                'down_payment'    => 0,
                'balance'         => 420000,
                'status'          => 'Pending',
                'payment_terms'   => '2 phases',
                'date'            => '2026-05-20',
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
        ]);
    }
}