<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\SalaryRecord;
use Illuminate\Http\Request;

class SalaryController extends Controller
{
    /** GET /admin/salary-records */
    public function index(Request $request)
    {
        $requested = $request->query('pay_period');
        $payPeriod = ($requested && preg_match('/^\d{4}-\d{2}-\d{2}$/', $requested))
            ? \Carbon\Carbon::createFromFormat('Y-m-d', $requested)->startOfWeek(\Carbon\Carbon::MONDAY)->format('Y-m-d')
            : $this->currentPayPeriod();

        $existingRecords = SalaryRecord::with('employee')
            ->where('pay_period', $payPeriod)
            ->get()
            ->keyBy('employee_id');

        $records = collect();

        // Regular employees are listed automatically every pay period.
        Employee::where('status', 'Active')
            ->where('employee_type', 'Regular')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get()
            ->each(function (Employee $employee) use (&$records, $existingRecords, $payPeriod) {
                $record = $existingRecords->get($employee->id);
                $records->push($record ? $this->format($record) : $this->virtualRow($employee, $payPeriod));
            });

        // Outsourced workers only appear once an admin has added them for this pay period.
        $existingRecords->each(function (SalaryRecord $record) use (&$records) {
            if (($record->employee->employee_type ?? 'Regular') === 'Outsourced') {
                $records->push($this->format($record));
            }
        });

        $records = $records->values();

        $summary = [
            'gross'      => $records->sum('gross_pay'),
            'deductions' => $records->sum('total_deductions'),
            'net'        => $records->sum('net_pay'),
        ];

        return response()->json(compact('records', 'summary', 'payPeriod'));
    }

    /** Monday of the current week, formatted "Y-m-d" */
    private function currentPayPeriod(): string
    {
        return now()->startOfWeek(\Carbon\Carbon::MONDAY)->format('Y-m-d');
    }

    /** Placeholder row for a regular employee with no salary record yet this pay period */
    private function virtualRow(Employee $employee, string $payPeriod): array
    {
        return [
            'id'               => null,
            'employee_id'      => $employee->id,
            'employee_name'    => $employee->full_name,
            'role'             => $employee->role ?? '—',
            'employee_type'    => $employee->employee_type ?? 'Regular',
            'pay_period'       => $payPeriod,
            'daily_rate'       => (float) ($employee->daily_rate ?? 0),
            'days_worked'      => 0,
            'overtime_hours'   => 0,
            'gross_pay'        => 0,
            'total_deductions' => 0,
            'net_pay'          => 0,
            'notes'            => null,
        ];
    }

    /** POST /admin/salary-records */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id'    => 'required|exists:employees,id',
            'pay_period'     => 'required|date_format:Y-m-d',
            'project_ids'    => 'nullable|array',
            'project_ids.*'  => 'exists:projects,id',
            'days_worked'    => 'required|numeric|min:0|max:7',
            'overtime_hours' => 'nullable|numeric|min:0|max:24',
            'notes'          => 'nullable|string|max:500',
        ]);

        $employee   = Employee::findOrFail($validated['employee_id']);
        $projectIds = array_filter((array) $request->input('project_ids', []));

        $data = SalaryRecord::compute([
            'employee_id'    => $employee->id,
            'project_id'     => count($projectIds) === 1 ? $projectIds[0] : null,
            'pay_period'     => $validated['pay_period'],
            'daily_rate'     => $employee->daily_rate ?? 0,
            'days_worked'    => $validated['days_worked'],
            'overtime_hours' => $validated['overtime_hours'] ?? 0,
            'notes'          => $validated['notes'] ?? null,
        ]);

        $record = SalaryRecord::updateOrCreate(
            ['employee_id' => $employee->id, 'pay_period' => $validated['pay_period']],
            $data
        );

        $record->projects()->sync($this->buildPivotData(
            $request->input('allocations', []),
            $projectIds,
            (float) $employee->daily_rate
        ));

        return response()->json(['record' => $this->format($record->load('employee', 'projects'))]);
    }

    /** PUT /admin/salary-records/{id} */
    public function update(Request $request, int $id)
    {
        $record = SalaryRecord::findOrFail($id);

        $validated = $request->validate([
            'days_worked'    => 'required|numeric|min:0|max:7',
            'overtime_hours' => 'nullable|numeric|min:0|max:24',
            'project_ids'    => 'nullable|array',
            'project_ids.*'  => 'exists:projects,id',
            'notes'          => 'nullable|string|max:500',
        ]);

        $projectIds = array_filter((array) $request->input('project_ids', []));

        $data = SalaryRecord::compute([
            'daily_rate'     => (float) $record->daily_rate,
            'days_worked'    => $validated['days_worked'],
            'overtime_hours' => $validated['overtime_hours'] ?? 0,
        ]);

        $record->update(array_merge($data, [
            'project_id' => count($projectIds) === 1 ? $projectIds[0] : null,
            'notes'      => $validated['notes'] ?? $record->notes,
        ]));

        $record->projects()->sync($this->buildPivotData(
            $request->input('allocations', []),
            $projectIds,
            (float) $record->daily_rate
        ));

        return response()->json(['record' => $this->format($record->load('employee', 'projects'))]);
    }

    /** DELETE /admin/salary-records/{id} */
    public function destroy(int $id)
    {
        SalaryRecord::findOrFail($id)->delete();
        return response()->json(['ok' => true]);
    }

    /**
     * Build pivot sync data: keyed by project_id with days/ot/allocated_pay.
     * Falls back to equal split when no per-project allocations provided.
     */
    private function buildPivotData(array $allocations, array $projectIds, float $dailyRate): array
    {
        $hourly   = $dailyRate / 8;
        $byId     = collect($allocations)->keyBy('project_id');
        $syncData = [];

        foreach ($projectIds as $pid) {
            $pid = (int) $pid;
            if ($byId->has($pid)) {
                $alloc = $byId[$pid];
                $d     = (float) ($alloc['days_worked']    ?? 0);
                $o     = (float) ($alloc['overtime_hours'] ?? 0);
                // Use pre-computed allocated_pay from client (equal split) if provided,
                // otherwise compute from days × rate (single-project fallback)
                $pay   = isset($alloc['allocated_pay']) && (float) $alloc['allocated_pay'] > 0
                    ? round((float) $alloc['allocated_pay'], 2)
                    : round($dailyRate * $d + $o * $hourly, 2);
            } else {
                $d = 0; $o = 0; $pay = 0;
            }
            $syncData[$pid] = [
                'days_worked'    => $d,
                'overtime_hours' => $o,
                'allocated_pay'  => $pay,
            ];
        }

        return $syncData;
    }

    private function format(SalaryRecord $r): array
    {
        // Always use the STORED daily_rate — never pull from current employee rate
        $dailyRate     = (float) $r->daily_rate;
        $daysWorked    = (float) $r->days_worked;
        $overtimeHours = (float) $r->overtime_hours;
        $hourlyRate    = $dailyRate / 8;
        $overtimePay   = $overtimeHours * $hourlyRate;

        return [
            'id'               => $r->id,
            'employee_id'      => $r->employee_id,
            'project_id'       => $r->project_id,
            'employee_name'    => $r->employee->full_name ?? '—',
            'role'             => $r->employee->role ?? '—',
            'employee_type'    => $r->employee->employee_type ?? 'Regular',
            'pay_period'       => $r->pay_period,
            'daily_rate'       => $dailyRate,          // snapshot — not current employee rate
            'days_worked'      => $daysWorked,
            'overtime_hours'   => $overtimeHours,
            'overtime_pay'     => round($overtimePay, 2),
            'gross_pay'        => (float) $r->gross_pay,
            'total_deductions' => (float) $r->total_deductions,
            'net_pay'          => (float) $r->net_pay,
            'notes'            => $r->notes,
            'project_ids'      => $r->relationLoaded('projects')
                ? $r->projects->pluck('id')->toArray()
                : ($r->project_id ? [$r->project_id] : []),
        ];
    }

    /** GET /admin/salary-projects — project list with client + spec for salary picker */
    public function projects()
    {
        $projects = \App\Models\Project::whereNotIn('status', ['completed', 'archived'])
            ->with('tankItems')
            ->orderBy('name')
            ->get()
            ->map(function ($p) {
                $spec = $p->tankItems->map(fn($t) => $t->quantity.'× '.($t->tank_type ?? '').($t->capacity ? ' ('.$t->capacity.')' : ''))->join(', ');
                return [
                    'id'     => $p->id,
                    'code'   => $p->code,
                    'name'   => $p->name,
                    'client' => $p->client,
                    'spec'   => $spec ?: ($p->tank_type ?? ''),
                ];
            });
        return response()->json($projects);
    }
}
