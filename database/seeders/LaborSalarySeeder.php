<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LaborSalarySeeder extends Seeder
{
    public function run(): void
    {
        // Clear any existing labor / salary data (PostgreSQL CASCADE)
        DB::statement('TRUNCATE TABLE project_labor CASCADE;');
        DB::statement('TRUNCATE TABLE salary_record_project CASCADE;');
        DB::statement('TRUNCATE TABLE salary_records CASCADE;');
        DB::statement('DELETE FROM project_employee;');

        $employees = DB::table('employees')
            ->where('employee_type', 'Regular')
            ->where('status', 'Active')
            ->orderBy('id')
            ->get();

        $projects = DB::table('projects')->orderBy('id')->get();

        // ── 1. Assign every regular employee to every project ──────────────
        foreach ($projects as $proj) {
            foreach ($employees as $emp) {
                DB::table('project_employee')->insertOrIgnore([
                    'project_id'  => $proj->id,
                    'employee_id' => $emp->id,
                    'created_at'  => $proj->start_date,
                    'updated_at'  => $proj->start_date,
                ]);
            }
        }

        // ── 2. Project labor (total hours per employee per project) ─────────
        foreach ($projects as $proj) {
            $days = max(1, (int) round((float) $proj->estimated_working_days));
            foreach ($employees as $emp) {
                $rate    = (float) $emp->daily_rate;
                $rph     = round($rate / 8, 4);
                $hours   = $days * 8;
                DB::table('project_labor')->insert([
                    'project_id'    => $proj->id,
                    'description'   => trim($emp->first_name . ' ' . $emp->last_name) . ' (' . $emp->role . ')',
                    'hours'         => $hours,
                    'rate_per_hour' => $rph,
                    'total_cost'    => number_format($rate * $days, 2, '.', ''),
                    'notes'         => null,
                    'status'        => 'active',
                    'created_at'    => $proj->start_date,
                    'updated_at'    => $proj->end_date,
                ]);
            }
        }

        // ── 3. Build salary records ─────────────────────────────────────────
        //
        // Strategy: collect every (week_start → [project_ids]) mapping across all
        // projects. Then for each employee, create ONE salary record per week and
        // link it to all projects active that week via the pivot.
        //
        // week_map: pay_period (YYYY-MM-DD) => [ project_id => days_worked_in_week ]

        $weekMap = [];   // ['2021-03-08'] => [7 => 5, 8 => 5, …]

        foreach ($projects as $proj) {
            $start   = Carbon::parse($proj->start_date);
            $end     = Carbon::parse($proj->end_date);
            $cursor  = $start->copy()->startOfWeek(Carbon::MONDAY);

            while ($cursor->lte($end)) {
                $pp         = $cursor->format('Y-m-d');
                $weekEnd    = $cursor->copy()->endOfWeek(Carbon::SUNDAY);
                $overlapStart = $cursor->copy()->max($start);
                $overlapEnd   = $weekEnd->copy()->min($end);

                // Count Mon–Sat working days in the overlap
                $days = 0;
                $d    = $overlapStart->copy();
                while ($d->lte($overlapEnd)) {
                    if ($d->dayOfWeek !== Carbon::SUNDAY) $days++;
                    $d->addDay();
                }
                $days = max(1, min(6, $days));

                $weekMap[$pp][$proj->id] = $days;
                $cursor->addWeek();
            }
        }

        // salary_record_id map: [employee_id][pay_period] => record_id
        $recordMap = [];

        foreach ($weekMap as $payPeriod => $projDaysMap) {
            $weekStart = Carbon::parse($payPeriod);

            // Is this the last pay period of the calendar month?
            $nextWeek        = $weekStart->copy()->addWeek();
            $applyDeductions = $nextWeek->month !== $weekStart->month;

            // Total unique project count for this week (for splitting the pivot)
            $projCount = count($projDaysMap);

            foreach ($employees as $emp) {
                $rate = (float) $emp->daily_rate;

                // Days worked = sum of per-project days (capped at 6 so no double-count)
                // When multiple projects overlap, the employee still works one set of days.
                // We use the MAX days across projects for the salary record itself.
                $totalDays = min(6, max(array_values($projDaysMap)));

                $grossPay      = round($rate * $totalDays, 2);
                $sss           = $applyDeductions ? 500.00  : 0;
                $philhealth    = $applyDeductions ? 200.00  : 0;
                $pagibig       = $applyDeductions ? 100.00  : 0;
                $totalDed      = $sss + $philhealth + $pagibig;
                $netPay        = round($grossPay - $totalDed, 2);

                $recordId = DB::table('salary_records')->insertGetId([
                    'employee_id'       => $emp->id,
                    'pay_period'        => $payPeriod,
                    'project_id'        => null,
                    'daily_rate'        => $rate,
                    'days_worked'       => $totalDays,
                    'overtime_hours'    => 0,
                    'sss'               => $sss,
                    'philhealth'        => $philhealth,
                    'pagibig'           => $pagibig,
                    'other_deductions'  => 0,
                    'extra_deductions'  => 0,
                    'apply_deductions'  => DB::raw($applyDeductions ? 'true' : 'false'),
                    'gross_pay'         => $grossPay,
                    'total_deductions'  => $totalDed,
                    'net_pay'           => $netPay,
                    'status'            => 'Paid',
                    'notes'             => null,
                    'created_at'        => $weekStart->toDateTimeString(),
                    'updated_at'        => $weekStart->toDateTimeString(),
                ]);

                $recordMap[$emp->id][$payPeriod] = $recordId;

                // Link to each project active this week via the pivot
                foreach ($projDaysMap as $projId => $projDays) {
                    $allocPay = round($rate * $projDays / $projCount, 2);
                    DB::table('salary_record_project')->insertOrIgnore([
                        'salary_record_id' => $recordId,
                        'project_id'       => $projId,
                        'days_worked'      => $projDays,
                        'overtime_hours'   => 0,
                        'allocated_pay'    => $allocPay,
                    ]);
                }
            }
        }

        $recordCount = DB::table('salary_records')->count();
        $pivotCount  = DB::table('salary_record_project')->count();
        $laborCount  = DB::table('project_labor')->count();

        $this->command->info("Labor records : {$laborCount}");
        $this->command->info("Salary records: {$recordCount}");
        $this->command->info("Pivot links   : {$pivotCount}");
    }
}
