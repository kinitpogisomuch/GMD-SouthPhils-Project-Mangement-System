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
            'days_worked'    => 'required|numeric|min:0|max:7',
            'overtime_hours' => 'nullable|numeric|min:0|max:24',
            'notes'          => 'nullable|string|max:500',
        ]);

        $employee = Employee::findOrFail($validated['employee_id']);

        $data = SalaryRecord::compute([
            'employee_id'    => $employee->id,
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

        return response()->json(['record' => $this->format($record->load('employee'))]);
    }

    /** PUT /admin/salary-records/{id} */
    public function update(Request $request, int $id)
    {
        $record = SalaryRecord::findOrFail($id);

        $validated = $request->validate([
            'days_worked'    => 'required|numeric|min:0|max:7',
            'overtime_hours' => 'nullable|numeric|min:0|max:24',
            'notes'          => 'nullable|string|max:500',
        ]);

        $data = SalaryRecord::compute([
            'daily_rate'     => $record->employee->daily_rate ?? 0,
            'days_worked'    => $validated['days_worked'],
            'overtime_hours' => $validated['overtime_hours'] ?? 0,
        ]);

        $record->update(array_merge($data, [
            'notes' => $validated['notes'] ?? $record->notes,
        ]));

        return response()->json(['record' => $this->format($record->load('employee'))]);
    }

    /** DELETE /admin/salary-records/{id} */
    public function destroy(int $id)
    {
        SalaryRecord::findOrFail($id)->delete();
        return response()->json(['ok' => true]);
    }

    private function format(SalaryRecord $r): array
    {
        return [
            'id'               => $r->id,
            'employee_id'      => $r->employee_id,
            'employee_name'    => $r->employee->full_name ?? '—',
            'role'             => $r->employee->role ?? '—',
            'employee_type'    => $r->employee->employee_type ?? 'Regular',
            'pay_period'       => $r->pay_period,
            'daily_rate'       => (float) ($r->employee->daily_rate ?? $r->daily_rate),
            'days_worked'      => (float) $r->days_worked,
            'overtime_hours'   => (float) $r->overtime_hours,
            'gross_pay'        => (float) $r->gross_pay,
            'total_deductions' => (float) $r->total_deductions,
            'net_pay'          => (float) $r->net_pay,
            'notes'            => $r->notes,
        ];
    }
}
