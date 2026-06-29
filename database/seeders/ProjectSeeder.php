<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProjectSeeder extends Seeder
{
    public function run(): void
    {
        // payments.project_id has no FK → must truncate explicitly before projects
        DB::statement('TRUNCATE TABLE payment_transactions CASCADE;');
        DB::statement('TRUNCATE TABLE payments CASCADE;');
        DB::statement('TRUNCATE TABLE projects CASCADE;');

        // All 8 phases in order
        $phaseKeys = ['planning','procurement','matl_prep','fabrication','inspection','painting','completion','delivery'];

        // Build a full phase_completion_status for a completed project
        $buildPhaseStatus = function (Carbon $start, Carbon $end) use ($phaseKeys): string {
            $totalDays = max($start->diffInDays($end), 8);
            $step      = (int) floor($totalDays / count($phaseKeys));
            $status    = [];
            foreach ($phaseKeys as $i => $phase) {
                $approvedAt = $start->copy()->addDays($step * ($i + 1));
                $status[$i] = [
                    'phase'       => $phase,
                    'approved'    => true,
                    'approved_at' => $approvedAt->toDateTimeString(),
                    'approved_by' => 1,
                    'phase_number'=> $i + 1,
                ];
            }
            return json_encode($status);
        };

        // Completed phase_data (planning sub-phases all approved)
        $buildPhaseData = function (Carbon $start): string {
            return json_encode([
                'planning' => [
                    'shop_drawing' => ['status' => 'approved', 'approved_at' => $start->toDateTimeString()],
                    'quotation'    => ['status' => 'approved', 'approved_at' => $start->copy()->addDays(3)->toDateTimeString()],
                    'payment'      => ['status' => 'approved', 'approved_at' => $start->copy()->addDays(5)->toDateTimeString()],
                ],
            ]);
        };

        // Raw project data straight from the client records
        $projects = [
            // 1
            ['client'=>'Powercity',            'address'=>'East service Road, Brgy. Cupang, Muntinlupa City', 'type'=>'Corporate',
             'name'=>'Fabrication of Fuel Day Tank',                   'tank_type'=>'Fuel Day Tank',
             'capacity'=>'3,000 liters', 'dimensions'=>'1.1m Ø x 2m (H)',     'qty'=>1,
             'start'=>'2026-05-04','end'=>'2026-05-14','days'=>10,'budget'=>180000,'total'=>110500],
            // 2
            ['client'=>'Alvin Magtibay',       'address'=>'Lucena City',                                      'type'=>'Individual Owner',
             'name'=>'Fabrication of Cooking Oil Storage Tank',        'tank_type'=>'Cooking Oil Storage Tank',
             'capacity'=>'22,000 liters','dimensions'=>'2.1m Ø x 3.1m (L)',   'qty'=>1,
             'start'=>'2026-04-10','end'=>'2026-05-01','days'=>20,'budget'=>280000,'total'=>215200],
            // 3
            ['client'=>'New JDT Trading',      'address'=>'Puerto Princesa, Palawan',                         'type'=>'Corporate',
             'name'=>'Fabrication of Underground Fuel Storage Tanks',  'tank_type'=>'Underground Fuel Storage Tank',
             'capacity'=>'8,000 liters', 'dimensions'=>'2.2m Ø x 6m (L)',     'qty'=>3,
             'start'=>'2025-08-10','end'=>'2025-09-10','days'=>30,'budget'=>750000,'total'=>650000],
            // 4
            ['client'=>'Innovative Agro',      'address'=>'Muntinlupa City',                                  'type'=>'Corporate',
             'name'=>'Fabrication of Aboveground Fuel Storage Tanks',  'tank_type'=>'Aboveground Fuel Storage Tank',
             'capacity'=>'18,000 liters','dimensions'=>'2.2m Ø x 4.8m (L)',   'qty'=>1,
             'start'=>'2025-10-15','end'=>'2025-11-15','days'=>30,'budget'=>250000,'total'=>210000],
            // 5
            ['client'=>'Hyundai Construction', 'address'=>'Sta. Rosa, Laguna',                                'type'=>'Corporate',
             'name'=>'Fabrication of Polymer Tanks',                   'tank_type'=>'Polymer Tank',
             'capacity'=>'60,000 liters','dimensions'=>'12m (L) x 2.1m (W) x 2.1m (H)', 'qty'=>3,
             'start'=>'2025-08-05','end'=>'2025-10-05','days'=>60,'budget'=>1500000,'total'=>1200000],
            // 6
            ['client'=>'Powercity',            'address'=>'East service Road, Brgy. Cupang, Muntinlupa City', 'type'=>'Corporate',
             'name'=>'Fabrication of Aboveground Fuel Storage Tanks',  'tank_type'=>'Aboveground Fuel Storage Tank',
             'capacity'=>'18,000 liters','dimensions'=>'2.2m Ø x 4.8m (L)',   'qty'=>1,
             'start'=>'2025-10-15','end'=>'2025-10-25','days'=>20,'budget'=>250000,'total'=>220000],
            // 7
            ['client'=>'Powercity',            'address'=>'East service Road, Brgy. Cupang, Muntinlupa City', 'type'=>'Corporate',
             'name'=>'Fabrication of Aboveground Fuel Storage Tanks',  'tank_type'=>'Aboveground Fuel Storage Tank',
             'capacity'=>'40,000 liters','dimensions'=>'2.4m Ø x 9m (L)',     'qty'=>2,
             'start'=>'2021-03-11','end'=>'2021-05-10','days'=>60,'budget'=>1220000,'total'=>950900],
            // 8
            ['client'=>'Powercity',            'address'=>'East service Road, Brgy. Cupang, Muntinlupa City', 'type'=>'Corporate',
             'name'=>'Fabrication of Fuel Day Tanks',                  'tank_type'=>'Fuel Day Tank',
             'capacity'=>'3,000 liters', 'dimensions'=>'1.2m Ø x 3m (L)',     'qty'=>2,
             'start'=>'2021-03-11','end'=>'2021-04-25','days'=>45,'budget'=>414400,'total'=>315200],
            // 9
            ['client'=>'Powercity',            'address'=>'East service Road, Brgy. Cupang, Muntinlupa City', 'type'=>'Corporate',
             'name'=>'Fabrication of Fuel Day Tanks',                  'tank_type'=>'Fuel Day Tank',
             'capacity'=>'4,000 liters', 'dimensions'=>'1.2m Ø x 3m (L)',     'qty'=>3,
             'start'=>'2021-04-10','end'=>'2021-05-25','days'=>45,'budget'=>772800,'total'=>625000],
            // 10
            ['client'=>'Powercity',            'address'=>'East service Road, Brgy. Cupang, Muntinlupa City', 'type'=>'Corporate',
             'name'=>'Fabrication of Fuel Day Tanks',                  'tank_type'=>'Fuel Day Tank',
             'capacity'=>'3,000 liters', 'dimensions'=>'2.2m Ø x 2.8m (L)',   'qty'=>1,
             'start'=>'2021-04-13','end'=>'2021-05-14','days'=>30,'budget'=>263000,'total'=>215000],
            // 11
            ['client'=>'Powercity',            'address'=>'East service Road, Brgy. Cupang, Muntinlupa City', 'type'=>'Corporate',
             'name'=>'Fabrication of Fuel Day Tanks',                  'tank_type'=>'Fuel Day Tank',
             'capacity'=>'3,000 liters', 'dimensions'=>'1.1m Ø x 2m (L)',     'qty'=>1,
             'start'=>'2021-01-13','end'=>'2021-01-28','days'=>15,'budget'=>180000,'total'=>125000],
            // 12
            ['client'=>'Sun Valley Golf Club', 'address'=>'Antipolo, Rizal',                                  'type'=>'Corporate',
             'name'=>'Fabrication of Aboveground Water Storage Tanks', 'tank_type'=>'Aboveground Water Storage Tank',
             'capacity'=>'24,000 liters','dimensions'=>'2.2m Ø x 6m (L)',     'qty'=>1,
             'start'=>'2024-05-11','end'=>'2024-06-10','days'=>30,'budget'=>488000,'total'=>390000],
            // 13
            ['client'=>'RVL Movers',           'address'=>'Muntinlupa City',                                  'type'=>'Corporate',
             'name'=>'Fabrication of Aboveground Fuel Storage Tanks',  'tank_type'=>'Aboveground Fuel Storage Tank',
             'capacity'=>'18,000 liters','dimensions'=>'2.2m Ø x 5m (L)',     'qty'=>1,
             'start'=>'2022-06-10','end'=>'2022-07-09','days'=>30,'budget'=>335000,'total'=>285000],
            // 14
            ['client'=>'Mario Moncada',        'address'=>'Cavite City',                                      'type'=>'Individual Owner',
             'name'=>'Fabrication of Underground Fuel Storage Tanks',  'tank_type'=>'Underground Fuel Storage Tank',
             'capacity'=>'8,000 liters', 'dimensions'=>'2.3m Ø x 5m (L)',     'qty'=>2,
             'start'=>'2021-05-17','end'=>'2021-06-18','days'=>30,'budget'=>340000,'total'=>295000],
            // 15
            ['client'=>'Word of Life',         'address'=>'Calauan, Laguna',                                  'type'=>'Corporate',
             'name'=>'Fabrication of Aboveground Fuel Storage Tanks',  'tank_type'=>'Aboveground Fuel Storage Tank',
             'capacity'=>'5,000 liters', 'dimensions'=>'2.1m Ø x 3m (L)',     'qty'=>1,
             'start'=>'2021-09-15','end'=>'2021-09-30','days'=>15,'budget'=>170000,'total'=>135000],
            // 16
            ['client'=>'Sal Lucero',           'address'=>'Leyte',                                            'type'=>'Individual Owner',
             'name'=>'Fabrication of Water Tank',                      'tank_type'=>'Water Tank',
             'capacity'=>'16,000 liters','dimensions'=>'2m Ø x 3m (L)',       'qty'=>1,
             'start'=>'2021-05-15','end'=>'2021-06-20','days'=>20,'budget'=>250000,'total'=>190000],
            // 17
            ['client'=>'Jonas Electrical',     'address'=>'Batangas City',                                    'type'=>'Corporate',
             'name'=>'Fabrication of Aboveground Storage Tank',        'tank_type'=>'Aboveground Storage Tank',
             'capacity'=>'10,000 liters','dimensions'=>'2.1m Ø x 2.4m (L)',   'qty'=>1,
             'start'=>'2022-04-10','end'=>'2022-04-25','days'=>15,'budget'=>210000,'total'=>175500],
            // 18
            ['client'=>'Bong De Guzman',       'address'=>'San Juan, Batangas',                               'type'=>'Individual Owner',
             'name'=>'Fabrication of Cooking Oil Storage Tank',        'tank_type'=>'Cooking Oil Storage Tank',
             'capacity'=>'24,000 liters','dimensions'=>'2.2m Ø x 6m (L)',     'qty'=>2,
             'start'=>'2025-01-05','end'=>'2025-02-05','days'=>30,'budget'=>570000,'total'=>430000],
            // 19
            ['client'=>"Total Safe Dev't.",    'address'=>'Cavite City',                                      'type'=>'Corporate',
             'name'=>'Fabrication of Aboveground Storage Tank',        'tank_type'=>'Aboveground Storage Tank',
             'capacity'=>'12,000 liters','dimensions'=>'2m Ø x 4m (L)',       'qty'=>1,
             'start'=>'2023-05-03','end'=>'2023-05-19','days'=>15,'budget'=>350000,'total'=>190000],
            // 20
            ['client'=>'Inspec Construction',  'address'=>'Mandaluyong City',                                 'type'=>'Corporate',
             'name'=>'Fabrication of Aboveground Storage Tank',        'tank_type'=>'Aboveground Storage Tank',
             'capacity'=>'20,000 liters','dimensions'=>'2.1m Ø x 5m (L)',     'qty'=>2,
             'start'=>'2022-06-05','end'=>'2022-07-20','days'=>45,'budget'=>950000,'total'=>710000],
        ];

        foreach ($projects as $p) {
            $start = Carbon::parse($p['start']);
            $end   = Carbon::parse($p['end']);
            $isBig = $p['budget'] >= 500000;

            // --- Project ---
            $projectId = DB::table('projects')->insertGetId([
                'name'                   => $p['name'],
                'client'                 => $p['client'],
                'contact_number'         => null,
                'email'                  => null,
                'address'                => $p['address'],
                'client_type'            => $p['type'],
                'tank_type'              => $p['tank_type'],
                'capacity'               => $p['capacity'],
                'dimensions'             => $p['dimensions'],
                'start_date'             => $p['start'],
                'end_date'               => $p['end'],
                'payment_status'         => 'Paid',
                'status'                 => 'completed',
                'progress'               => 100,
                'duration'               => $p['days'] . ' days',
                'estimated_working_days' => $p['days'],
                'notes'                  => null,
                'contract_amount'        => $p['budget'],
                'project_type'           => $isBig ? 'big_project' : 'small_project',
                'current_phase'          => 'delivery',
                'current_sub_phase'      => null,
                'total_phases'           => 8,
                'completion_percentage'  => 100,
                'phase_completion_status'=> $buildPhaseStatus($start, $end),
                'phase_data'             => $buildPhaseData($start),
                'last_phase_update_at'   => $end->toDateTimeString(),
                'last_phase_updated_by'  => 1,
                'created_at'             => $start->toDateTimeString(),
                'updated_at'             => $end->toDateTimeString(),
            ]);

            // --- Tank Item ---
            $shapeMap = [
                'Underground Fuel Storage Tank'  => 'Cylindrical',
                'Cooking Oil Storage Tank'        => 'Cylindrical',
                'Polymer Tank'                    => 'Rectangular',
                'Aboveground Water Storage Tank'  => 'Cylindrical',
                'Aboveground Fuel Storage Tank'   => 'Cylindrical',
                'Aboveground Storage Tank'        => 'Cylindrical',
                'Fuel Day Tank'                   => 'Cylindrical',
                'Water Tank'                      => 'Cylindrical',
            ];
            $shape = $shapeMap[$p['tank_type']] ?? null;

            DB::table('project_tank_items')->insert([
                'project_id'  => $projectId,
                'tank_type'   => $p['tank_type'],
                'shape'       => $shape,
                'capacity'    => $p['capacity'],
                'dimensions'  => $p['dimensions'],
                'quantity'    => $p['qty'],
                'notes'       => null,
                'sort_order'  => 0,
                'created_at'  => $start->toDateTimeString(),
                'updated_at'  => $end->toDateTimeString(),
            ]);

            // --- Payment ---
            // Amounts match Payment::stageAmounts(): big=50/30/20, small=50/50
            $total       = $p['total'];
            $downAmount  = round($total * 0.50, 2);
            $midAmount   = $isBig ? round($total * 0.30, 2) : 0;
            $finalAmount = round($total - $downAmount - $midAmount, 2);
            $midPoint    = $start->copy()->addDays((int) ($p['days'] / 2));

            $paymentId = DB::table('payments')->insertGetId([
                'project_id'        => $projectId,
                'client'            => $p['client'],
                'client_type'       => $p['type'],
                'contract_amount'   => $total,
                'down_payment'      => $downAmount,
                'balance'           => 0,
                'status'            => 'Fully Paid',
                'payment_terms'     => $isBig
                    ? '3 phases: 50% down, 30% progress, 20% final'
                    : '2 phases: 50% down, 50% final',
                'payment_term_type' => $isBig ? 'big_project' : 'small_project',
                'date'              => $p['start'],
                'created_at'        => $start->toDateTimeString(),
                'updated_at'        => $end->toDateTimeString(),
            ]);

            // --- Payment Transactions (all stages recorded = Fully Paid) ---
            $transactions = [[
                'payment_id'       => $paymentId,
                'payment_stage'    => 'down_payment',
                'amount_paid'      => $downAmount,
                'payment_date'     => $p['start'],
                'reference_number' => 'REF-' . $projectId . '-DP',
                'notes'            => 'Down payment received.',
                'recorded_by'      => 'Admin',
                'created_at'       => $start->toDateTimeString(),
                'updated_at'       => $start->toDateTimeString(),
            ]];

            if ($isBig) {
                $transactions[] = [
                    'payment_id'       => $paymentId,
                    'payment_stage'    => 'progress_payment',
                    'amount_paid'      => $midAmount,
                    'payment_date'     => $midPoint->toDateString(),
                    'reference_number' => 'REF-' . $projectId . '-PP',
                    'notes'            => 'Progress payment received.',
                    'recorded_by'      => 'Admin',
                    'created_at'       => $midPoint->toDateTimeString(),
                    'updated_at'       => $midPoint->toDateTimeString(),
                ];
            }

            $transactions[] = [
                'payment_id'       => $paymentId,
                'payment_stage'    => 'final_payment',
                'amount_paid'      => $finalAmount,
                'payment_date'     => $p['end'],
                'reference_number' => 'REF-' . $projectId . '-FP',
                'notes'            => 'Final payment received upon delivery.',
                'recorded_by'      => 'Admin',
                'created_at'       => $end->toDateTimeString(),
                'updated_at'       => $end->toDateTimeString(),
            ];

            DB::table('payment_transactions')->insert($transactions);

            // --- Materials (realistic BOM per tank type) ---
            $this->seedMaterials($projectId, $p['tank_type'], $p['qty'], $p['total'], $start);

            // --- Material Purchases (actual buys, slightly varied from BOM) ---
            $this->seedPurchases($projectId, $start, $end);

            // --- Material Usage (consumption logged by employees) ---
            $this->seedUsages($projectId, $start, $end);
        }
    }

    private function seedUsages(int $projectId, Carbon $start, Carbon $end): void
    {
        $usedFor = [
            'Steel & Metal Stock'  => 'Tank body fabrication',
            'Welding Supplies'     => 'Welding seams and joints',
            'Cutting & Grinding'   => 'Cutting and shaping steel plates',
            'Gas & Fuel'           => 'Oxy-acetylene cutting',
            'Paint & Coating'      => 'Primer and topcoat application',
            'Brushes & Tools'      => 'Surface painting',
            'Safety & PPE'         => 'Worker protection during fabrication',
            'Abrasives'            => 'Surface preparation and finishing',
            'Inspection & Testing' => 'Pressure and leak testing',
        ];

        $recorders = [
            'Andres Cabanban', 'Terence Almanza', 'Robert Pineda',
            'Sammy Maceda',    'Robert Olan',
        ];

        // Usage is logged during fabrication: 20%–80% through the project timeline
        $totalDays    = max(1, $start->diffInDays($end));
        $fabStart     = $start->copy()->addDays((int)($totalDays * 0.20));
        $fabEnd       = $start->copy()->addDays((int)($totalDays * 0.80));

        $purchases = DB::table('material_purchases')
            ->where('project_id', $projectId)
            ->get();

        $rows = [];
        foreach ($purchases as $pur) {
            $qtyBought = (float) $pur->qty_bought;

            // Use 75–95% of purchased quantity
            $seed        = $projectId * 31 + $pur->id * 7;
            $usePct      = 0.75 + (($seed % 21) / 100);   // 0.75 … 0.95
            $qtyUsed     = round($qtyBought * $usePct, 2);
            if ($qtyUsed <= 0) continue;

            // Spread usage over 1–2 log entries per material
            $splits = ($qtyBought > 3 && ($seed % 3) !== 0) ? 2 : 1;

            for ($i = 0; $i < $splits; $i++) {
                $portion   = ($i === 0 && $splits === 2)
                    ? round($qtyUsed * 0.6, 2)
                    : ($splits === 2 ? round($qtyUsed * 0.4, 2) : $qtyUsed);
                if ($portion <= 0) continue;

                $dayRange  = max(1, $fabStart->diffInDays($fabEnd));
                $dayOffset = ($seed + $i * 13) % $dayRange;
                $usedDate  = $fabStart->copy()->addDays($dayOffset)->toDateString();
                $recorder  = $recorders[($seed + $i) % count($recorders)];

                $category = DB::table('project_materials')
                    ->where('id', $pur->project_material_id)
                    ->value('category') ?? 'General';

                $rows[] = [
                    'project_id'          => $projectId,
                    'project_material_id' => $pur->project_material_id,
                    'material_name'       => $pur->material_name,
                    'quantity_used'       => $portion,
                    'unit'                => $pur->unit,
                    'used_date'           => $usedDate,
                    'used_for'            => $usedFor[$category] ?? 'Tank fabrication',
                    'notes'               => null,
                    'recorded_by'         => $recorder,
                    'status'              => 'active',
                    'created_at'          => $fabStart->copy()->addDays($dayOffset)->toDateTimeString(),
                    'updated_at'          => $fabStart->copy()->addDays($dayOffset)->toDateTimeString(),
                ];
            }
        }

        if ($rows) {
            DB::table('material_usages')->insert($rows);
        }
    }

    private function seedPurchases(int $projectId, Carbon $start, Carbon $end): void
    {
        $suppliers = [
            'Steel & Metal Stock'  => 'J & B Steel',
            'Welding Supplies'     => 'Nine Golden Hardware',
            'Cutting & Grinding'   => 'EMZ 7 Hardware',
            'Gas & Fuel'           => 'Lanfeng Fuel Pump',
            'Paint & Coating'      => 'Leared Enterprises',
            'Brushes & Tools'      => 'An Yiac Hardware',
            'Safety & PPE'         => 'New Alfred Hardware',
            'Abrasives'            => 'Symbolic Trading',
            'Inspection & Testing' => 'Steeltrust Corporation',
        ];

        // Buy window: procurement happens in the first 40% of the project timeline
        $procureEnd = $start->copy()->addDays(max(3, (int)(($end->diffInDays($start)) * 0.40)));

        $materials = DB::table('project_materials')
            ->where('project_id', $projectId)
            ->where('status', 'active')
            ->get();

        $rows = [];
        foreach ($materials as $mat) {
            // Skip ~15% of minor items randomly to reflect real-world incomplete records
            if (in_array($mat->material_name, ['Cotton Gloves', 'Clear Glass', 'Sanding Paper #100'])
                && ($mat->project_id % 3 === 0)) {
                continue;
            }

            // Actual unit cost: ±8% variation from BOM estimate
            $variation      = 1 + (($projectId * 7 + strlen($mat->material_name)) % 17 - 8) / 100;
            $actualUnitCost = round((float)$mat->price_per_unit * $variation, 2);
            $qty            = (float)$mat->quantity;
            $totalPaid      = round($qty * $actualUnitCost, 2);

            // Spread purchase dates across the procurement window
            $seed        = $projectId + $mat->id;
            $dayOffset   = $seed % max(1, $start->diffInDays($procureEnd));
            $purchaseDate= $start->copy()->addDays($dayOffset)->toDateString();

            $rows[] = [
                'project_id'          => $projectId,
                'project_material_id' => $mat->id,
                'material_name'       => $mat->material_name,
                'unit'                => $mat->unit,
                'qty_bought'          => $qty,
                'actual_unit_cost'    => $actualUnitCost,
                'total_paid'          => $totalPaid,
                'supplier'            => $suppliers[$mat->category] ?? 'Local Supplier',
                'purchase_date'       => $purchaseDate,
                'notes'               => null,
                'created_at'          => $start->toDateTimeString(),
                'updated_at'          => $start->toDateTimeString(),
            ];
        }

        if ($rows) {
            DB::table('material_purchases')->insert($rows);
        }
    }

    private function seedMaterials(int $projectId, string $tankType, int $qty, float $total, Carbon $start): void
    {
        // MS Plates qty scales with project size (primary cost driver)
        $plates = max($qty, (int) round($total / 55000));
        $s      = $qty; // other consumables scale with tank count

        $bom = $this->bomFor(strtolower($tankType), $s, $plates);

        $rows = [];
        foreach ($bom as [$name, $cat, $q, $unit, $ppu]) {
            $rows[] = [
                'project_id'     => $projectId,
                'material_name'  => $name,
                'category'       => $cat,
                'quantity'       => max(1, $q),
                'unit'           => $unit,
                'price_per_unit' => $ppu,
                'total_cost'     => round(max(1, $q) * $ppu, 2),
                'notes'          => null,
                'status'         => 'active',
                'created_at'     => $start->toDateTimeString(),
                'updated_at'     => $start->toDateTimeString(),
            ];
        }

        DB::table('project_materials')->insert($rows);
    }

    /** Returns catalog-only BOM rows [name, category, qty, unit, price] per tank type */
    private function bomFor(string $type, int $s, int $plates): array
    {
        // ── Underground Fuel Storage Tank ────────────────────────────────────
        if (str_contains($type, 'underground')) {
            return [
                ['MS Plates',            'Steel & Metal Stock',  $plates,   'pcs',       4500],
                ['Angular Bar',          'Steel & Metal Stock',  $s * 4,    'pcs',        650],
                ['Electrode (7018)',     'Welding Supplies',     $s * 8,    'kilos',      320],
                ['Welding Gloves',       'Welding Supplies',     $s * 2,    'pcs',        200],
                ['Grinding Disc #7',     'Cutting & Grinding',   $s * 6,    'pcs',         85],
                ['Cutting Disc #5',      'Cutting & Grinding',   $s * 4,    'pcs',         70],
                ['Industrial Oxygen',    'Gas & Fuel',           $s * 3,    'cylinders',  750],
                ['Acetylene',            'Gas & Fuel',           $s * 3,    'cylinders', 1100],
                ['Epoxy Primer Gray',    'Paint & Coating',      $s * 2,    'galons',    2200],
                ['Paint Thinner',        'Paint & Coating',      $s * 1,    'galons',     450],
                ['Paint Brush',          'Brushes & Tools',      $s * 3,    'pcs',        120],
                ['Dark Glass #11',       'Safety & PPE',         $s * 2,    'pcs',        350],
                ['Cotton Gloves',        'Safety & PPE',         $s * 6,    'pcs',         85],
                ['Sanding Paper #60',    'Abrasives',            $s * 12,   'pcs',         45],
                ['Penetrant Dye Spray',  'Inspection & Testing', $s * 2,    'pairs',     1350],
                ['Pressure Test Kits',   'Inspection & Testing', $s * 1,    'set',       3500],
                ['Pressure Gauge 60PSI', 'Inspection & Testing', $s * 1,    'pcs',        950],
            ];
        }

        // ── Cooking Oil Storage Tank ──────────────────────────────────────────
        if (str_contains($type, 'cooking')) {
            return [
                ['MS Plates',            'Steel & Metal Stock',  $plates,   'pcs',       4500],
                ['Angular Bar',          'Steel & Metal Stock',  $s * 4,    'pcs',        650],
                ['Electrode (6011)',     'Welding Supplies',     $s * 5,    'kilos',      280],
                ['Welding Gloves',       'Welding Supplies',     $s * 2,    'pcs',        200],
                ['Grinding Disc #4',     'Cutting & Grinding',   $s * 8,    'pcs',         65],
                ['Cutting Disc #4',      'Cutting & Grinding',   $s * 6,    'pcs',         65],
                ['Industrial Oxygen',    'Gas & Fuel',           $s * 2,    'cylinders',  750],
                ['Acetylene',            'Gas & Fuel',           $s * 2,    'cylinders', 1100],
                ['Epoxy Primer Gray',    'Paint & Coating',      $s * 2,    'galons',    2200],
                ['Lacquer Thinner',      'Paint & Coating',      $s * 1,    'galons',     550],
                ['Paint Brush',          'Brushes & Tools',      $s * 3,    'pcs',        120],
                ['Roller Brush',         'Brushes & Tools',      $s * 2,    'pcs',        200],
                ['Clear Glass',          'Safety & PPE',         $s * 2,    'pcs',        180],
                ['Cotton Gloves',        'Safety & PPE',         $s * 4,    'pcs',         85],
                ['Sanding Paper #100',   'Abrasives',            $s * 10,   'pcs',         45],
                ['Penetrant Dye Spray',  'Inspection & Testing', $s * 1,    'pairs',     1350],
                ['Pressure Test Kits',   'Inspection & Testing', $s * 1,    'set',       3500],
                ['Pressure Gauge 60PSI', 'Inspection & Testing', $s * 1,    'pcs',        950],
            ];
        }

        // ── Water Tank / Aboveground Water Storage Tank ───────────────────────
        if (str_contains($type, 'water')) {
            return [
                ['MS Plates',            'Steel & Metal Stock',  $plates,   'pcs',       4500],
                ['Angular Bar',          'Steel & Metal Stock',  $s * 4,    'pcs',        650],
                ['Electrode (6011)',     'Welding Supplies',     $s * 5,    'kilos',      280],
                ['Welding Gloves',       'Welding Supplies',     $s * 2,    'pcs',        200],
                ['Grinding Disc #4',     'Cutting & Grinding',   $s * 6,    'pcs',         65],
                ['Cutting Disc #4',      'Cutting & Grinding',   $s * 4,    'pcs',         65],
                ['Industrial Oxygen',    'Gas & Fuel',           $s * 2,    'cylinders',  750],
                ['Acetylene',            'Gas & Fuel',           $s * 2,    'cylinders', 1100],
                ['Epoxy Primer Gray',    'Paint & Coating',      $s * 2,    'galons',    2200],
                ['Polituff Putty',       'Paint & Coating',      $s * 1,    'galons',    1200],
                ['Lacquer Thinner',      'Paint & Coating',      $s * 1,    'galons',     550],
                ['Paint Brush',          'Brushes & Tools',      $s * 3,    'pcs',        120],
                ['Roller Brush',         'Brushes & Tools',      $s * 2,    'pcs',        200],
                ['Clear Glass',          'Safety & PPE',         $s * 2,    'pcs',        180],
                ['Cotton Gloves',        'Safety & PPE',         $s * 4,    'pcs',         85],
                ['Sanding Paper #100',   'Abrasives',            $s * 8,    'pcs',         45],
                ['Penetrant Dye Spray',  'Inspection & Testing', $s * 1,    'pairs',     1350],
                ['Pressure Test Kits',   'Inspection & Testing', $s * 1,    'set',       3500],
            ];
        }

        // ── Polymer Tank ──────────────────────────────────────────────────────
        if (str_contains($type, 'polymer')) {
            return [
                ['Angular Bar',          'Steel & Metal Stock',  $s * 8,    'pcs',        650],
                ['MS Plates',            'Steel & Metal Stock',  $s * 2,    'pcs',       4500],
                ['Electrode (6011)',     'Welding Supplies',     $s * 3,    'kilos',      280],
                ['Welding Gloves',       'Welding Supplies',     $s * 2,    'pcs',        200],
                ['Grinding Disc #4',     'Cutting & Grinding',   $s * 6,    'pcs',         65],
                ['Cutting Disc #4',      'Cutting & Grinding',   $s * 4,    'pcs',         65],
                ['Epoxy Primer Gray',    'Paint & Coating',      $s * 2,    'galons',    2200],
                ['Lacquer Thinner',      'Paint & Coating',      $s * 1,    'galons',     550],
                ['Paint Brush',          'Brushes & Tools',      $s * 3,    'pcs',        120],
                ['Clear Glass',          'Safety & PPE',         $s * 2,    'pcs',        180],
                ['Cotton Gloves',        'Safety & PPE',         $s * 4,    'pcs',         85],
                ['Sanding Paper #100',   'Abrasives',            $s * 8,    'pcs',         45],
                ['Pressure Test Kits',   'Inspection & Testing', $s * 1,    'set',       3500],
                ['Pressure Gauge 60PSI', 'Inspection & Testing', $s * 1,    'pcs',        950],
            ];
        }

        // ── Fuel Day Tank (small, short lead time) ────────────────────────────
        if (str_contains($type, 'fuel day')) {
            return [
                ['MS Plates',            'Steel & Metal Stock',  $plates,   'pcs',       4500],
                ['Angular Bar',          'Steel & Metal Stock',  $s * 3,    'pcs',        650],
                ['Electrode (6011)',     'Welding Supplies',     $s * 4,    'kilos',      280],
                ['Welding Gloves',       'Welding Supplies',     $s * 1,    'pcs',        200],
                ['Grinding Disc #4',     'Cutting & Grinding',   $s * 6,    'pcs',         65],
                ['Cutting Disc #4',      'Cutting & Grinding',   $s * 4,    'pcs',         65],
                ['Industrial Oxygen',    'Gas & Fuel',           $s * 1,    'cylinders',  750],
                ['Acetylene',            'Gas & Fuel',           $s * 1,    'cylinders', 1100],
                ['Epoxy Primer Gray',    'Paint & Coating',      $s * 1,    'galons',    2200],
                ['QDE Medium Gray',      'Paint & Coating',      $s * 1,    'galons',    1800],
                ['Lacquer Thinner',      'Paint & Coating',      $s * 1,    'galons',     550],
                ['Paint Brush',          'Brushes & Tools',      $s * 2,    'pcs',        120],
                ['Dark Glass #11',       'Safety & PPE',         $s * 1,    'pcs',        350],
                ['Cotton Gloves',        'Safety & PPE',         $s * 4,    'pcs',         85],
                ['Sanding Paper #60',    'Abrasives',            $s * 8,    'pcs',         45],
                ['Penetrant Dye Spray',  'Inspection & Testing', $s * 1,    'pairs',     1350],
                ['Pressure Test Kits',   'Inspection & Testing', $s * 1,    'set',       3500],
                ['Pressure Gauge 60PSI', 'Inspection & Testing', $s * 1,    'pcs',        950],
            ];
        }

        // ── Default: Aboveground Fuel / Generic Storage Tank ──────────────────
        return [
            ['MS Plates',            'Steel & Metal Stock',  $plates,   'pcs',       4500],
            ['Angular Bar',          'Steel & Metal Stock',  $s * 4,    'pcs',        650],
            ['Electrode (6011)',     'Welding Supplies',     $s * 5,    'kilos',      280],
            ['Electrode (7018)',     'Welding Supplies',     $s * 3,    'kilos',      320],
            ['Welding Gloves',       'Welding Supplies',     $s * 2,    'pcs',        200],
            ['Grinding Disc #5',     'Cutting & Grinding',   $s * 8,    'pcs',         70],
            ['Cutting Disc #5',      'Cutting & Grinding',   $s * 6,    'pcs',         70],
            ['Industrial Oxygen',    'Gas & Fuel',           $s * 2,    'cylinders',  750],
            ['Acetylene',            'Gas & Fuel',           $s * 2,    'cylinders', 1100],
            ['Epoxy Primer Gray',    'Paint & Coating',      $s * 2,    'galons',    2200],
            ['QDE Medium Gray',      'Paint & Coating',      $s * 2,    'galons',    1800],
            ['Paint Thinner',        'Paint & Coating',      $s * 1,    'galons',     450],
            ['Paint Brush',          'Brushes & Tools',      $s * 3,    'pcs',        120],
            ['Roller Brush',         'Brushes & Tools',      $s * 2,    'pcs',        200],
            ['Dark Glass #11',       'Safety & PPE',         $s * 2,    'pcs',        350],
            ['Cotton Gloves',        'Safety & PPE',         $s * 6,    'pcs',         85],
            ['Sanding Paper #60',    'Abrasives',            $s * 10,   'pcs',         45],
            ['Penetrant Dye Spray',  'Inspection & Testing', $s * 1,    'pairs',     1350],
            ['Pressure Test Kits',   'Inspection & Testing', $s * 1,    'set',       3500],
            ['Pressure Gauge 60PSI', 'Inspection & Testing', $s * 1,    'pcs',        950],
        ];
    }
}
