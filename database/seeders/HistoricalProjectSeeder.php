<?php

namespace Database\Seeders;

use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * GMD's historical (2021–2026) project records, from gmd_final-1.pdf.
 *
 * Additive and re-runnable: it never touches live data. Everything it creates is
 * tagged (projects.notes / salary_records.notes / monthly_expenses.description start
 * with HISTORICAL_TAG) and a re-run deletes only those rows before seeding again.
 *
 * Labor and overhead are NOT taken from the sheet's labor column — they are rebuilt
 * the way the app records them:
 *   - Labor = the employees' salaries. Every crew member works Mon–Sat (6 days a week)
 *     while at least one project is active (start date → actual completion date). A
 *     day's pay is split equally across the projects active that day, so overlapping
 *     projects share the labor cost instead of each paying a full day.
 *   - Overhead = the monthly expenses (Rent ₱28,000 + Electricity ₱7,200), split
 *     equally across the projects active in that month — the same equal split the
 *     Monthly Expenses "Save Allocation" button uses.
 *
 * Run: php artisan db:seed --class=HistoricalProjectSeeder
 */
class HistoricalProjectSeeder extends Seeder
{
    const HISTORICAL_TAG = 'Historical record';

    // The waste/handling allowance on materials — already inside the sheet's estimated material cost.
    const MATERIAL_FACTOR = 10;

    // The owner's profit on every past project, as a % of the Project Budget.
    const MARKUP = 10;

    // The crew and their daily rates at the time (from the sheet).
    const CREW = [
        ['first' => 'Andres',  'last' => 'Cabanban', 'role' => 'Welder',       'rate' => 700],
        ['first' => 'Robert',  'last' => 'Olan',     'role' => 'Helper/Labor', 'rate' => 400],
        ['first' => 'Robert',  'last' => 'Pineda',   'role' => 'Welder',       'rate' => 550],
        ['first' => 'Sammy',   'last' => 'Maceda',   'role' => 'Helper/Labor', 'rate' => 400],
        ['first' => 'Terence', 'last' => 'Almanza',  'role' => 'Fabricator',   'rate' => 450],
    ];

    // Monthly expenses (from the sheet), split across the month's active projects.
    const MONTHLY_EXPENSES = [
        ['category' => 'Rent',        'amount' => 28000],
        ['category' => 'Electricity', 'amount' => 7200],
    ];

    /**
     * One row per project, as in the sheet. 'finish' = Actual Finish Date/Completion,
     * 'lead' = lead time in days, costs are the sheet's estimated/actual material cost
     * and estimated labor cost, 'pay' = [stage, amount, date] as received.
     *
     * Corrected obvious typos in the sheet (see the notes on the rows):
     *   - Sal Lucero DP 1,250,000 → 125,000 (50% of 250,000)
     *   - Hyundai DP/FP 1,250,000 each → 750,000 each (50% of 1,500,000); FP date 2026 → 2025
     *   - New JDT progress billing / final payment dates 2026 → 2025
     *   - Mario Moncada DP 140,000 → 170,000 (50% DP of 340,000; DP + FP must equal the contract)
     *   - Powercity fuel day tanks (414,400) DP/FP 207,000 → 207,200 each (50% of 414,400)
     */
    private function projects(): array
    {
        return [
            ['client' => 'Powercity', 'address' => 'East Service Road, Brgy. Cupang, Muntinlupa City', 'type' => 'Corporate',
             'name' => 'Fabrication of Fuel Day Tanks', 'contract' => 180000, 'dimensions' => '1.1m Ø x 2m (L)', 'capacity' => '3,000 liters',
             'start' => '2021-01-13', 'end' => '2021-01-30', 'finish' => '2021-01-28', 'lead' => 15, 'qty' => 1,
             'est_labor' => 37500, 'est_mat' => 96250, 'act_mat' => 87500,
             'pay' => [['down_payment', 90000, '2021-01-15'], ['final_payment', 90000, '2021-02-02']]],

            ['client' => 'Powercity', 'address' => 'East Service Road, Brgy. Cupang, Muntinlupa City', 'type' => 'Corporate',
             'name' => 'Fabrication of Aboveground Fuel Storage Tanks', 'contract' => 1220000, 'dimensions' => '2.4m Ø x 9m (L)', 'capacity' => '40,000 liters',
             'start' => '2021-03-11', 'end' => '2021-05-10', 'finish' => '2021-05-10', 'lead' => 60, 'qty' => 2,
             'est_labor' => 150000, 'est_mat' => 880990, 'act_mat' => 800900,
             'pay' => [['down_payment', 610000, '2021-03-13'], ['final_payment', 610000, '2021-05-15']]],

            ['client' => 'Powercity', 'address' => 'East Service Road, Brgy. Cupang, Muntinlupa City', 'type' => 'Corporate',
             'name' => 'Fabrication of Fuel Day Tanks', 'contract' => 414400, 'dimensions' => '1.2m Ø x 3m (L)', 'capacity' => '3,000 liters',
             'start' => '2021-03-11', 'end' => '2021-04-25', 'finish' => '2021-04-25', 'lead' => 45, 'qty' => 2,
             'est_labor' => 112500, 'est_mat' => 222970, 'act_mat' => 202700,
             // sheet: 207,000 each — corrected to 50% of 414,400
             'pay' => [['down_payment', 207200, '2021-03-15'], ['final_payment', 207200, '2021-05-05']]],

            ['client' => 'Powercity', 'address' => 'East Service Road, Brgy. Cupang, Muntinlupa City', 'type' => 'Corporate',
             'name' => 'Fabrication of Fuel Day Tanks', 'contract' => 772800, 'dimensions' => '1.2m Ø x 3m (L)', 'capacity' => '4,000 liters',
             'start' => '2021-04-10', 'end' => '2021-05-28', 'finish' => '2021-05-25', 'lead' => 45, 'qty' => 3,
             'est_labor' => 112500, 'est_mat' => 563750, 'act_mat' => 512500,
             'pay' => [['down_payment', 386400, '2021-04-10'], ['final_payment', 386400, '2021-05-27']]],

            ['client' => 'Powercity', 'address' => 'East Service Road, Brgy. Cupang, Muntinlupa City', 'type' => 'Corporate',
             'name' => 'Fabrication of Fuel Day Tanks', 'contract' => 263000, 'dimensions' => '2.2m Ø x 2.8m (L)', 'capacity' => '3,000 liters',
             'start' => '2021-04-13', 'end' => '2021-05-17', 'finish' => '2021-05-14', 'lead' => 30, 'qty' => 1,
             'est_labor' => 75000, 'est_mat' => 154000, 'act_mat' => 140000,
             'pay' => [['down_payment', 131500, '2021-04-14'], ['final_payment', 131500, '2021-05-17']]],

            ['client' => 'Sal Lucero', 'address' => 'Leyte', 'type' => 'Individual Owner',
             'name' => 'Fabrication of Water Tank', 'contract' => 250000, 'dimensions' => '2m Ø x 3m (L)', 'capacity' => '16,000 liters',
             'start' => '2021-05-15', 'end' => '2021-06-20', 'finish' => '2021-06-20', 'lead' => 20, 'qty' => 1,
             'est_labor' => 50000, 'est_mat' => 154000, 'act_mat' => 140000,
             // sheet: DP 1,250,000 — corrected to 125,000
             'pay' => [['down_payment', 125000, '2021-05-17'], ['final_payment', 125000, '2021-06-20']]],

            ['client' => 'Mario Moncada', 'address' => 'Cavite City', 'type' => 'Individual Owner',
             'name' => 'Fabrication of Underground Fuel Storage Tanks', 'contract' => 340000, 'dimensions' => '2.3m Ø x 5m (L)', 'capacity' => '8,000 liters',
             'start' => '2021-05-17', 'end' => '2021-06-22', 'finish' => '2021-06-18', 'lead' => 30, 'qty' => 2,
             'est_labor' => 75000, 'est_mat' => 242000, 'act_mat' => 220000,
             // sheet: DP 140,000 — corrected to 170,000 (50% DP of 340,000)
             'pay' => [['down_payment', 170000, '2021-05-19'], ['final_payment', 170000, '2021-06-19']]],

            ['client' => 'Word of Life', 'address' => 'Calauan, Laguna', 'type' => 'Corporate',
             'name' => 'Fabrication of Aboveground Fuel Storage Tanks', 'contract' => 170000, 'dimensions' => '2.1m Ø x 3m (L)', 'capacity' => '5,000 liters',
             'start' => '2021-09-15', 'end' => '2021-09-30', 'finish' => '2021-09-30', 'lead' => 15, 'qty' => 1,
             'est_labor' => 37500, 'est_mat' => 107250, 'act_mat' => 97500,
             'pay' => [['down_payment', 85000, '2021-09-15'], ['final_payment', 85000, '2021-09-30']]],

            ['client' => 'Jonas Electrical', 'address' => 'Batangas City', 'type' => 'Corporate',
             'name' => 'Fabrication of Aboveground Storage Tank', 'contract' => 210000, 'dimensions' => '2.1m Ø x 2.4m (L)', 'capacity' => '10,000 liters',
             'start' => '2022-04-10', 'end' => '2022-04-25', 'finish' => '2022-04-25', 'lead' => 15, 'qty' => 1,
             'est_labor' => 37500, 'est_mat' => 151800, 'act_mat' => 138000,
             'pay' => [['down_payment', 105000, '2022-04-11'], ['final_payment', 105000, '2022-04-27']]],

            ['client' => 'Inspec Construction', 'address' => 'Mandaluyong City', 'type' => 'Corporate',
             'name' => 'Fabrication of Aboveground Storage Tank', 'contract' => 950000, 'dimensions' => '2.1m Ø x 6m (L)', 'capacity' => '20,000 liters',
             'start' => '2022-06-05', 'end' => '2022-07-20', 'finish' => '2022-07-20', 'lead' => 45, 'qty' => 2,
             'est_labor' => 112500, 'est_mat' => 657250, 'act_mat' => 597500,
             'pay' => [['down_payment', 475000, '2022-06-07'], ['final_payment', 475000, '2022-07-25']]],

            ['client' => 'RVL Movers', 'address' => 'Muntinlupa City', 'type' => 'Corporate',
             'name' => 'Fabrication of Aboveground Fuel Storage Tanks', 'contract' => 335000, 'dimensions' => '2.2m Ø x 5m (L)', 'capacity' => '18,000 liters',
             'start' => '2022-06-10', 'end' => '2022-07-09', 'finish' => '2022-07-09', 'lead' => 30, 'qty' => 1,
             'est_labor' => 75000, 'est_mat' => 231000, 'act_mat' => 210000,
             'pay' => [['down_payment', 167500, '2022-06-10'], ['final_payment', 167500, '2022-07-09']]],

            ['client' => "Total Safe Dev't.", 'address' => 'Cavite City', 'type' => 'Corporate',
             'name' => 'Fabrication of Aboveground Storage Tank', 'contract' => 350000, 'dimensions' => '2m Ø x 4m (L)', 'capacity' => '12,000 liters',
             'start' => '2023-05-03', 'end' => '2023-05-19', 'finish' => '2023-05-19', 'lead' => 15, 'qty' => 1,
             'est_labor' => 37500, 'est_mat' => 167750, 'act_mat' => 152500,
             'pay' => [['down_payment', 175000, '2023-05-05'], ['final_payment', 175000, '2023-05-22']]],

            ['client' => 'Sun Valley Golf Club', 'address' => 'Antipolo, Rizal', 'type' => 'Corporate',
             'name' => 'Fabrication of Aboveground Water Storage Tanks', 'contract' => 488000, 'dimensions' => '2.2m Ø x 6m (L)', 'capacity' => '24,000 liters',
             'start' => '2024-05-11', 'end' => '2024-06-12', 'finish' => '2024-06-10', 'lead' => 30, 'qty' => 1,
             'est_labor' => 75000, 'est_mat' => 346500, 'act_mat' => 315000,
             'pay' => [['down_payment', 244000, '2024-05-13'], ['final_payment', 244000, '2024-06-10']]],

            ['client' => 'Bong De Guzman', 'address' => 'San Juan, Batangas', 'type' => 'Individual Owner',
             'name' => 'Fabrication of Cooking Oil Storage Tank', 'contract' => 570000, 'dimensions' => '2.2m Ø x 6m (L)', 'capacity' => '24,000 liters',
             'start' => '2025-01-05', 'end' => '2025-02-05', 'finish' => '2025-02-05', 'lead' => 30, 'qty' => 2,
             'est_labor' => 75000, 'est_mat' => 390500, 'act_mat' => 355000,
             'pay' => [['down_payment', 285000, '2025-01-10'], ['final_payment', 285000, '2025-02-07']]],

            ['client' => 'Hyundai Construction', 'address' => 'Sta. Rosa, Laguna', 'type' => 'Corporate',
             'name' => 'Fabrication of Polymer Tanks', 'contract' => 1500000, 'dimensions' => '12m (L) x 2.1m (W) x 2.1m (H)', 'capacity' => '60,000 liters',
             'start' => '2025-08-05', 'end' => '2025-10-10', 'finish' => '2025-10-05', 'lead' => 60, 'qty' => 3,
             'est_labor' => 150000, 'est_mat' => 1155000, 'act_mat' => 1050000,
             // sheet: DP/FP 1,250,000 each and FP dated Oct 15, 2026 — corrected
             'pay' => [['down_payment', 750000, '2025-08-07'], ['final_payment', 750000, '2025-10-15']]],

            ['client' => 'New JDT Trading', 'address' => 'Puerto Princesa, Palawan', 'type' => 'Corporate',
             'name' => 'Fabrication of Underground Fuel Storage Tanks', 'contract' => 750000, 'dimensions' => '2.2m Ø x 6m (L)', 'capacity' => '8,000 liters',
             'start' => '2025-08-10', 'end' => '2025-09-15', 'finish' => '2025-09-10', 'lead' => 30, 'qty' => 3,
             'est_labor' => 75000, 'est_mat' => 632500, 'act_mat' => 575000, 'terms' => 'big_project',
             // sheet: progress billing / final payment dated 2026 — corrected to 2025
             'pay' => [['down_payment', 375000, '2025-08-11'], ['progress_payment', 300000, '2025-08-30'], ['final_payment', 75000, '2025-09-13']]],

            ['client' => 'Powercity', 'address' => 'East Service Road, Brgy. Cupang, Muntinlupa City', 'type' => 'Corporate',
             'name' => 'Fabrication of Aboveground Fuel Storage Tanks', 'contract' => 250000, 'dimensions' => '2.2m Ø x 4.8m (L)', 'capacity' => '18,000 liters',
             'start' => '2025-10-05', 'end' => '2025-10-25', 'finish' => '2025-10-25', 'lead' => 20, 'qty' => 1,
             'est_labor' => 50000, 'est_mat' => 187000, 'act_mat' => 170000,
             'pay' => [['down_payment', 125000, '2025-10-10'], ['final_payment', 125000, '2025-10-28']]],

            ['client' => 'Innovative Agro', 'address' => 'Muntinlupa City', 'type' => 'Corporate',
             'name' => 'Fabrication of Aboveground Fuel Storage Tanks', 'contract' => 250000, 'dimensions' => '2.2m Ø x 4.8m (L)', 'capacity' => '18,000 liters',
             'start' => '2025-10-15', 'end' => '2025-11-20', 'finish' => '2025-11-15', 'lead' => 30, 'qty' => 1,
             'est_labor' => 75000, 'est_mat' => 148500, 'act_mat' => 135000,
             'pay' => [['down_payment', 125000, '2025-10-15'], ['final_payment', 125000, '2025-11-16']]],

            ['client' => 'Alvin Magtibay', 'address' => 'Lucena City', 'type' => 'Individual Owner',
             'name' => 'Fabrication of Cooking Oil Storage Tank', 'contract' => 280000, 'dimensions' => '2.1m Ø x 3.1m (L)', 'capacity' => '22,000 liters',
             'start' => '2026-04-10', 'end' => '2026-05-03', 'finish' => '2026-04-30', 'lead' => 20, 'qty' => 1,
             'est_labor' => 50000, 'est_mat' => 181720, 'act_mat' => 165200,
             'pay' => [['down_payment', 140000, '2026-04-13'], ['final_payment', 140000, '2026-05-05']]],

            ['client' => 'Powercity', 'address' => 'East Service Road, Brgy. Cupang, Muntinlupa City', 'type' => 'Corporate',
             'name' => 'Fabrication of Fuel Day Tank', 'contract' => 180000, 'dimensions' => '1.1m Ø x 2m (H)', 'capacity' => '3,000 liters',
             'start' => '2026-05-04', 'end' => '2026-05-20', 'finish' => '2026-05-14', 'lead' => 10, 'qty' => 1,
             'est_labor' => 25000, 'est_mat' => 94050, 'act_mat' => 85500,
             'pay' => [['down_payment', 90000, '2026-05-05'], ['final_payment', 90000, '2026-05-15']]],
        ];
    }

    public function run(): void
    {
        // These projects are imported already finished; their payments and costs are created after them.
        \App\Models\Project::withoutFinancialCheck(fn () => $this->importProjects());
    }

    private function importProjects(): void
    {
        DB::transaction(function () {
            $this->removePreviousRun();

            $crew = $this->crewEmployees();
            $windows = [];   // project_id => [start, finish] — the active (working) window

            foreach ($this->projects() as $p) {
                $projectId = $this->seedProject($p, $crew);
                $windows[$projectId] = [Carbon::parse($p['start']), Carbon::parse($p['finish'])];
            }

            $this->seedPayroll($crew, $windows);
            $this->seedMonthlyExpenses($windows);
        });

        $this->command?->info('Historical projects: ' . count($this->projects()));
        $this->command?->info('Salary records    : ' . DB::table('salary_records')->where('notes', 'like', self::HISTORICAL_TAG . '%')->count());
        $this->command?->info('Expense months    : ' . DB::table('monthly_expenses')->where('description', 'like', self::HISTORICAL_TAG . '%')->distinct()->count('month_year'));
    }

    /** Deletes only what an earlier run of this seeder created. */
    private function removePreviousRun(): void
    {
        $projectIds = DB::table('projects')->where('notes', 'like', self::HISTORICAL_TAG . '%')->pluck('id');

        if ($projectIds->isNotEmpty()) {
            $paymentIds = DB::table('payments')->whereIn('project_id', $projectIds)->pluck('id');
            DB::table('payment_transactions')->whereIn('payment_id', $paymentIds)->delete();
            DB::table('payments')->whereIn('id', $paymentIds)->delete();
            // materials, purchases, usage, labor, tank items, crew links and allocations cascade
            DB::table('projects')->whereIn('id', $projectIds)->delete();
        }

        $salaryIds = DB::table('salary_records')->where('notes', 'like', self::HISTORICAL_TAG . '%')->pluck('id');
        DB::table('salary_record_project')->whereIn('salary_record_id', $salaryIds)->delete();
        DB::table('salary_records')->whereIn('id', $salaryIds)->delete();

        // (their per-project allocations went with the projects above — that FK cascades)
        DB::table('monthly_expenses')->where('description', 'like', self::HISTORICAL_TAG . '%')->delete();
    }

    /**
     * The crew's employee ids with their historical daily rates. Anyone missing from the
     * employee list (Robert Pineda) is added as archived — they no longer work for GMD
     * but their past payroll needs an owner.
     */
    private function crewEmployees(): array
    {
        $crew = [];
        foreach (self::CREW as $c) {
            $id = DB::table('employees')->where('first_name', $c['first'])->where('last_name', $c['last'])->value('id');
            if (!$id) {
                $id = DB::table('employees')->insertGetId([
                    'first_name'    => $c['first'],
                    'last_name'     => $c['last'],
                    'role'          => $c['role'],
                    'employee_type' => 'Regular',
                    'pay_type'      => 'Daily',
                    'daily_rate'    => $c['rate'],
                    'status'        => 'Inactive',   // shown as "Archived" in the employee list
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);
            }
            $crew[] = ['id' => $id, 'name' => $c['first'] . ' ' . $c['last'], 'role' => $c['role'], 'rate' => (float) $c['rate']];
        }

        return $crew;
    }

    private function seedProject(array $p, array $crew): int
    {
        $start    = Carbon::parse($p['start']);
        $finish   = Carbon::parse($p['finish']);
        $terms    = $p['terms'] ?? 'small_project';
        $finalPay = collect($p['pay'])->firstWhere(0, 'final_payment');
        // delivered when the final payment came in (paid upon delivery), never before completion
        $delivered = Carbon::parse($finalPay[2])->max($finish);
        $tankType  = $this->tankType($p['name']);

        $projectId = DB::table('projects')->insertGetId([
            'name'                    => $p['name'],
            'client'                  => $p['client'],
            'client_id'               => $this->clientId($p['client']),
            'address'                 => $p['address'],
            'client_type'             => $p['type'],
            'tank_type'               => $tankType,
            'capacity'                => $p['capacity'],
            'dimensions'              => $p['dimensions'],
            'start_date'              => $p['start'],
            'end_date'                => $p['end'],
            'payment_status'          => 'Paid',
            'status'                  => 'completed',
            'completed_at'            => $finish->copy()->setTime(12, 0),
            'progress'                => 100,
            'duration'                => $p['lead'] . ' days',
            'estimated_working_days'  => $p['lead'],
            'notes'                   => self::HISTORICAL_TAG . ' (imported from GMD project records).',
            'contract_amount'         => $p['contract'],
            'project_type'            => $terms,
            'current_phase'           => 'delivery',
            'total_phases'            => 8,
            'completion_percentage'   => 100,
            'phase_completion_status' => json_encode($this->phaseStatus($start, $finish, $delivered)),
            'phase_data'              => json_encode([
                'planning' => [
                    'shop_drawing' => ['status' => 'approved', 'approved_at' => $start->toDateTimeString()],
                    'quotation'    => ['status' => 'approved', 'approved_at' => $start->toDateTimeString()],
                    'payment'      => ['status' => 'approved', 'approved_at' => $start->toDateTimeString()],
                ],
                'delivery' => ['delivered_at' => $delivered->toDateString()],
            ]),
            'last_phase_update_at'    => $delivered->toDateTimeString(),
            'last_phase_updated_by'   => 1,
            'created_at'              => $start->toDateTimeString(),
            'updated_at'              => $delivered->toDateTimeString(),
        ]);

        DB::table('project_tank_items')->insert([
            'project_id' => $projectId,
            'tank_type'  => $tankType,
            'shape'      => str_contains($tankType, 'Polymer') ? 'Rectangular' : 'Cylindrical',
            'capacity'   => $p['capacity'],
            'dimensions' => $p['dimensions'],
            'quantity'   => $p['qty'],
            'sort_order' => 0,
            'created_at' => $start->toDateTimeString(),
            'updated_at' => $start->toDateTimeString(),
        ]);

        foreach ($crew as $c) {
            DB::table('project_employee')->insert([
                'project_id' => $projectId, 'employee_id' => $c['id'],
                'created_at' => $start->toDateTimeString(), 'updated_at' => $start->toDateTimeString(),
            ]);
        }

        $this->seedEstimatedLabor($projectId, $p, $crew, $start);
        $this->seedMaterials($projectId, $p, $tankType, $start, $finish);
        $this->seedPayment($projectId, $p, $terms, $start, $delivered);

        return $projectId;
    }

    /** Estimated labor (the BOM's labor lines): each crew member × the lead time — totals the sheet's estimate. */
    private function seedEstimatedLabor(int $projectId, array $p, array $crew, Carbon $start): void
    {
        foreach ($crew as $c) {
            DB::table('project_labor')->insert([
                'project_id'    => $projectId,
                'description'   => $c['name'] . ' (' . $c['role'] . ')',
                'hours'         => $p['lead'] * 8,
                'rate_per_hour' => round($c['rate'] / 8, 4),
                'daily_rate'    => $c['rate'],
                'total_cost'    => round($c['rate'] * $p['lead'], 2),
                'status'        => 'active',
                'created_at'    => $start->toDateTimeString(),
                'updated_at'    => $start->toDateTimeString(),
            ]);
        }
    }

    /**
     * BOM lines totalling the sheet's Estimated Material Cost, purchases totalling the Actual
     * Material Cost, and usage logs. The MS Plates line (the main cost driver) absorbs whatever
     * the consumables don't cover, so both totals match the sheet to the peso.
     *
     * The sheet's estimate already includes the 10% material factor, so each line is stored at its
     * base cost with MATERIAL_FACTOR on top — like a quotation built in the system — and base × 1.10
     * gives back the sheet's estimate exactly.
     */
    private function seedMaterials(int $projectId, array $p, string $tankType, Carbon $start, Carbon $finish): void
    {
        $lines = $this->bomFor(strtolower($tankType), $p['qty']);
        $consumables = collect($lines)->reject(fn ($l) => $l[0] === 'MS Plates')->sum(fn ($l) => $l[2] * $l[4]);
        $platesTotal = round($this->estimatedMaterials($p) - $consumables, 2);
        $toBase      = fn (float $v) => round($v / (1 + self::MATERIAL_FACTOR / 100), 2);
        $baseTotal   = $toBase($this->estimatedMaterials($p));
        $baseOthers  = collect($lines)->reject(fn ($l) => $l[0] === 'MS Plates')->sum(fn ($l) => $toBase(round($l[2] * $l[4], 2)));

        $ratio      = $p['act_mat'] / $this->estimatedMaterials($p);          // actual vs estimate, applied to every line
        $suppliers  = $this->supplierNames();
        $procureDays = max(2, (int) ($start->diffInDays($finish) * 0.4));   // buying happens early in the job
        $spent      = 0;
        $purchaseIds = [];

        foreach ($lines as $i => [$name, $category, $qty, $unit, $price]) {
            $isPlates = $name === 'MS Plates';
            if ($isPlates) {
                $qty   = max(1, (int) round($platesTotal / 4500));
                $total = $platesTotal;
                $price = round($total / $qty, 2);
            } else {
                $total = round($qty * $price, 2);
            }

            // base cost (factor added on top); the plates line takes the rounding so the base lines total estimate ÷ 1.10
            $base = $isPlates ? round($baseTotal - $baseOthers, 2) : $toBase($total);

            $materialId = DB::table('project_materials')->insertGetId([
                'project_id'     => $projectId,
                'material_name'  => $name,
                'category'       => $category,
                'quantity'       => $qty,
                'unit'           => $unit,
                'price_per_unit' => round($base / max($qty, 1), 2),
                'total_cost'     => $base,
                'factor'         => self::MATERIAL_FACTOR,
                'status'         => 'active',
                'created_at'     => $start->toDateTimeString(),
                'updated_at'     => $start->toDateTimeString(),
            ]);

            if (!$isPlates) {
                $paid = round($total * $ratio, 2);
                $spent += $paid;
                $purchaseIds[] = $this->insertPurchase($projectId, $materialId, $name, $unit, $qty, $paid,
                    $suppliers[$i % count($suppliers)], $start->copy()->addDays($i % $procureDays));
            } else {
                $platesRow = [$materialId, $name, $unit, $qty];
            }
        }

        // MS Plates purchase closes the gap to the sheet's actual material cost exactly
        [$materialId, $name, $unit, $qty] = $platesRow;
        $purchaseIds[] = $this->insertPurchase($projectId, $materialId, $name, $unit, $qty,
            round($p['act_mat'] - $spent, 2), $suppliers[0], $start->copy());

        $this->seedUsage($projectId, $purchaseIds, $start, $finish);
    }

    private function insertPurchase(int $projectId, int $materialId, string $name, string $unit, float $qty, float $paid, string $supplier, Carbon $date): int
    {
        return DB::table('material_purchases')->insertGetId([
            'project_id'          => $projectId,
            'project_material_id' => $materialId,
            'material_name'       => $name,
            'unit'                => $unit,
            'qty_bought'          => $qty,
            'actual_unit_cost'    => round($paid / max($qty, 1), 2),
            'total_paid'          => $paid,
            'supplier'            => $supplier,
            'purchase_date'       => $date->toDateString(),
            'created_at'          => $date->toDateTimeString(),
            'updated_at'          => $date->toDateTimeString(),
        ]);
    }

    /** Usage logs: 75–95% of each purchase used during fabrication (20%–80% of the window). */
    private function seedUsage(int $projectId, array $purchaseIds, Carbon $start, Carbon $finish): void
    {
        $span     = max(1, $start->diffInDays($finish));
        $fabStart = $start->copy()->addDays((int) ($span * 0.2));
        $fabDays  = max(1, (int) ($span * 0.6));
        $names    = array_map(fn ($c) => $c['first'] . ' ' . $c['last'], self::CREW);

        foreach (DB::table('material_purchases')->whereIn('id', $purchaseIds)->get() as $i => $pur) {
            $usedOn = $fabStart->copy()->addDays(($projectId + $i * 3) % $fabDays);
            DB::table('material_usages')->insert([
                'project_id'          => $projectId,
                'project_material_id' => $pur->project_material_id,
                'material_name'       => $pur->material_name,
                'quantity_used'       => round((float) $pur->qty_bought * (0.75 + (($projectId + $i) % 21) / 100), 2),
                'unit'                => $pur->unit,
                'used_date'           => $usedOn->toDateString(),
                'used_for'            => 'Tank fabrication',
                'recorded_by'         => $names[$i % count($names)],
                'status'              => 'active',
                'created_at'          => $usedOn->toDateTimeString(),
                'updated_at'          => $usedOn->toDateTimeString(),
            ]);
        }
    }

    /**
     * Past projects carry the standard markup: the contract (fixed by what the client paid) is the
     * Project Budget + MARKUP %, so the budget is contract ÷ 1.10.
     */
    private function projectBudget(array $p): float
    {
        return round($p['contract'] / (1 + self::MARKUP / 100), 2);
    }

    /** Estimated materials (with the 10% factor) = the budget less the estimated labor. */
    private function estimatedMaterials(array $p): float
    {
        return round($this->projectBudget($p) - $p['est_labor'], 2);
    }

    private function seedPayment(int $projectId, array $p, string $terms, Carbon $start, Carbon $delivered): void
    {
        $budget = $this->projectBudget($p);   // contract ÷ 1.10 — the standard 10% markup

        $paymentId = DB::table('payments')->insertGetId([
            'project_id'        => $projectId,
            'client'            => $p['client'],
            'client_type'       => $p['type'],
            'contract_amount'   => $p['contract'],
            'down_payment'      => round($p['contract'] * 0.5, 2),
            'balance'           => $p['contract'],
            'status'            => 'Pending Down Payment',
            'payment_terms'     => $terms === 'big_project' ? '3 Phases (50% / 30% / 20%)' : '2 Phases (50% / 50%)',
            'payment_term_type' => $terms,
            'project_budget'    => $budget,
            'markup'            => round($p['contract'] - $budget, 2),
            'date'              => $p['pay'][0][2],
            'created_at'        => $start->toDateTimeString(),
            'updated_at'        => $delivered->toDateTimeString(),
        ]);

        foreach ($p['pay'] as [$stage, $amount, $date]) {
            DB::table('payment_transactions')->insert([
                'payment_id'       => $paymentId,
                'payment_stage'    => $stage,
                'amount_paid'      => $amount,
                'payment_date'     => $date,
                'reference_number' => null,   // no OR number on record — the receipts list shows "—"
                'notes'            => self::HISTORICAL_TAG . '.',
                'recorded_by'      => 'Admin',
                'created_at'       => Carbon::parse($date)->toDateTimeString(),
                'updated_at'       => Carbon::parse($date)->toDateTimeString(),
            ]);
        }

        Payment::find($paymentId)->recalculate();   // balance + status from the transactions
    }

    /**
     * Payroll: one weekly salary record per crew member for every week with project work.
     * Mon–Sat are working days (6 a week); a day counts when at least one project is active
     * on it, and that day's pay is split equally across the projects active that day.
     */
    private function seedPayroll(array $crew, array $windows): void
    {
        // working day (Y-m-d) => [project ids active that day]
        $days = [];
        foreach ($windows as $projectId => [$start, $finish]) {
            for ($d = $start->copy(); $d->lte($finish); $d->addDay()) {
                if ($d->dayOfWeek !== Carbon::SUNDAY) {
                    $days[$d->toDateString()][] = $projectId;
                }
            }
        }
        ksort($days);

        // week (Monday) => [day => project ids]
        $weeks = [];
        foreach ($days as $day => $projectIds) {
            $weeks[Carbon::parse($day)->startOfWeek(Carbon::MONDAY)->toDateString()][$day] = $projectIds;
        }

        foreach ($weeks as $monday => $weekDays) {
            // share of the week's days that belongs to each project (a shared day = 1 / n each)
            $dayShare = [];
            foreach ($weekDays as $projectIds) {
                foreach ($projectIds as $pid) {
                    $dayShare[$pid] = ($dayShare[$pid] ?? 0) + 1 / count($projectIds);
                }
            }
            $daysWorked = count($weekDays);
            $weekStart  = Carbon::parse($monday);

            foreach ($crew as $c) {
                $gross = round($c['rate'] * $daysWorked, 2);

                $recordId = DB::table('salary_records')->insertGetId([
                    'employee_id'      => $c['id'],
                    'pay_period'       => $monday,
                    'project_id'       => count($dayShare) === 1 ? array_key_first($dayShare) : null,
                    'daily_rate'       => $c['rate'],
                    'days_worked'      => $daysWorked,
                    'gross_pay'        => $gross,
                    'total_deductions' => 0,
                    'net_pay'          => $gross,
                    'status'           => 'Paid',
                    'payment_method'   => 'cash',
                    'notes'            => self::HISTORICAL_TAG . ' — payroll split across the week\'s active projects.',
                    'created_at'       => $weekStart->toDateTimeString(),
                    'updated_at'       => $weekStart->toDateTimeString(),
                ]);

                // split the gross pay by each project's share of the days; the last project
                // takes the rounding remainder so the allocations add up to the gross exactly
                $allocated = 0;
                $pids = array_keys($dayShare);
                foreach ($pids as $k => $pid) {
                    $pay = $k === count($pids) - 1
                        ? round($gross - $allocated, 2)
                        : round($c['rate'] * $dayShare[$pid], 2);
                    $allocated += $pay;

                    DB::table('salary_record_project')->insert([
                        'salary_record_id' => $recordId,
                        'project_id'       => $pid,
                        'days_worked'      => round($dayShare[$pid], 2),
                        'allocated_pay'    => $pay,
                    ]);
                }
            }
        }
    }

    /**
     * Monthly expenses for every month with project work, split equally across that month's
     * active projects (the same equal split as Monthly Expenses → Save Allocation).
     */
    private function seedMonthlyExpenses(array $windows): void
    {
        $months = [];   // 'Y-m' => [project ids active that month]
        foreach ($windows as $projectId => [$start, $finish]) {
            for ($m = $start->copy()->startOfMonth(); $m->lte($finish); $m->addMonth()) {
                $months[$m->format('Y-m')][] = $projectId;
            }
        }
        ksort($months);

        $monthTotal = array_sum(array_column(self::MONTHLY_EXPENSES, 'amount'));

        foreach ($months as $month => $projectIds) {
            // never overwrite a month that already has real (non-historical) expenses
            if (DB::table('monthly_expenses')->where('month_year', $month)->exists()) {
                continue;
            }
            $stamp = Carbon::parse($month . '-01')->endOfMonth()->toDateTimeString();

            foreach (self::MONTHLY_EXPENSES as $e) {
                DB::table('monthly_expenses')->insert([
                    'month_year'  => $month,
                    'category'    => $e['category'],
                    'description' => self::HISTORICAL_TAG . ' — ' . $e['category'],
                    'amount'      => $e['amount'],
                    'created_at'  => $stamp,
                    'updated_at'  => $stamp,
                ]);
            }

            $n   = count($projectIds);
            $per = floor($monthTotal * 100 / $n) / 100;
            foreach (array_values($projectIds) as $i => $pid) {
                DB::table('monthly_expense_projects')->insert([
                    'month_year'       => $month,
                    'project_id'       => $pid,
                    'allocated_amount' => $i === $n - 1 ? round($monthTotal - $per * ($n - 1), 2) : $per,
                    'created_at'       => $stamp,
                    'updated_at'       => $stamp,
                ]);
            }
        }
    }

    // -------------------------------------------------------------------------

    /** Links Powercity / New JDT to their existing client accounts; others stay name-only. */
    private function clientId(string $client): ?int
    {
        $prefix = ['Powercity' => 'Powercity%', 'New JDT Trading' => 'New JDT%'][$client] ?? null;

        return $prefix ? DB::table('clients')->where('name', 'like', $prefix)->value('id') : null;
    }

    private function supplierNames(): array
    {
        // the suppliers saved in Supplier Contacts, so purchases name real suppliers
        $names = DB::table('supplier_contacts')->orderBy('id')->pluck('name')->all();

        return $names ?: ['Local Supplier'];
    }

    private function tankType(string $name): string
    {
        $type = preg_replace('/^Fabrication of\s+/i', '', $name);
        $type = preg_replace('/\bTanks\b/', 'Tank', $type);

        return ucwords($type);
    }

    private function phaseStatus(Carbon $start, Carbon $finish, Carbon $delivered): array
    {
        $phases = ['planning', 'procurement', 'matl_prep', 'fabrication', 'inspection', 'painting', 'completion', 'delivery'];
        $span   = max(7, $start->diffInDays($finish));
        $status = [];
        foreach ($phases as $i => $phase) {
            $at = $phase === 'delivery'
                ? $delivered
                : ($phase === 'completion' ? $finish : $start->copy()->addDays((int) round($span * ($i + 1) / 7)));
            $status[$i] = [
                'phase'        => $phase,
                'approved'     => true,
                'approved_at'  => $at->toDateTimeString(),
                'approved_by'  => 1,
                'phase_number' => $i + 1,
            ];
        }

        return $status;
    }

    /** Consumables per tank type [name, category, qty, unit, unit price]; MS Plates is sized later. */
    private function bomFor(string $type, int $s): array
    {
        $plates = ['MS Plates', 'Steel & Metal Stock', 1, 'pcs', 4500];

        if (str_contains($type, 'polymer')) {
            return [
                $plates,
                ['Angular Bar',          'Steel & Metal Stock',  $s * 8,  'pcs',        650],
                ['Electrode (6011)',     'Welding Supplies',     $s * 3,  'kilos',      280],
                ['Welding Gloves',       'Welding Supplies',     $s * 2,  'pcs',        200],
                ['Grinding Disc #4',     'Cutting & Grinding',   $s * 6,  'pcs',         65],
                ['Cutting Disc #4',      'Cutting & Grinding',   $s * 4,  'pcs',         65],
                ['Epoxy Primer Gray',    'Paint & Coating',      $s * 2,  'galons',    2200],
                ['Lacquer Thinner',      'Paint & Coating',      $s * 1,  'galons',     550],
                ['Paint Brush',          'Brushes & Tools',      $s * 3,  'pcs',        120],
                ['Cotton Gloves',        'Safety & PPE',         $s * 4,  'pcs',         85],
                ['Sanding Paper #100',   'Abrasives',            $s * 8,  'pcs',         45],
                ['Pressure Test Kits',   'Inspection & Testing', $s * 1,  'set',       3500],
            ];
        }

        $base = [
            $plates,
            ['Angular Bar',          'Steel & Metal Stock',  $s * 4,  'pcs',        650],
            ['Electrode (6011)',     'Welding Supplies',     $s * 5,  'kilos',      280],
            ['Welding Gloves',       'Welding Supplies',     $s * 2,  'pcs',        200],
            ['Grinding Disc #4',     'Cutting & Grinding',   $s * 6,  'pcs',         65],
            ['Cutting Disc #4',      'Cutting & Grinding',   $s * 4,  'pcs',         65],
            ['Industrial Oxygen',    'Gas & Fuel',           $s * 2,  'cylinders',  750],
            ['Acetylene',            'Gas & Fuel',           $s * 2,  'cylinders', 1100],
            ['Epoxy Primer Gray',    'Paint & Coating',      $s * 2,  'galons',    2200],
            ['Lacquer Thinner',      'Paint & Coating',      $s * 1,  'galons',     550],
            ['Paint Brush',          'Brushes & Tools',      $s * 3,  'pcs',        120],
            ['Cotton Gloves',        'Safety & PPE',         $s * 4,  'pcs',         85],
            ['Sanding Paper #60',    'Abrasives',            $s * 8,  'pcs',         45],
            ['Pressure Test Kits',   'Inspection & Testing', $s * 1,  'set',       3500],
        ];

        if (str_contains($type, 'underground') || str_contains($type, 'fuel')) {
            $base[] = ['Penetrant Dye Spray',  'Inspection & Testing', $s * 2, 'pairs', 1350];
            $base[] = ['Pressure Gauge 60PSI', 'Inspection & Testing', $s * 1, 'pcs',    950];
        }

        return $base;
    }
}
