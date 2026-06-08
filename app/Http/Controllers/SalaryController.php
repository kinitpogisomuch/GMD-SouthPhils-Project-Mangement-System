<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\SalaryRecord;
use Illuminate\Http\Request;

class SalaryController extends Controller
{
    /** GET /admin/salary-records?period=2026-05 */
    public function index(Request $request)
    {
        $period = $request->query('period', now()->format('Y-m'));

        $records = SalaryRecord::with('employee')
            ->where('pay_period', $period)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($r) => $this->format($r));

        $summary = [
            'gross'      => $records->sum('gross_pay'),
            'deductions' => $records->sum('total_deductions'),
            'net'        => $records->sum('net_pay'),
        ];

        return response()->json(compact('records', 'summary'));
    }

    /** POST /admin/salary-records */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id'      => 'required|exists:employees,id',
            'pay_period'       => 'required|date_format:Y-m-d',  // week-start Monday
            'days_worked'      => 'required|numeric|min:0|max:5',
            'overtime_hours'   => 'nullable|numeric|min:0',
            'extra_deductions' => 'nullable|numeric|min:0',
            'apply_deductions' => 'nullable|boolean',
            'status'           => 'nullable|in:Pending,Paid',
            'notes'            => 'nullable|string|max:500',
        ]);

        $employee = Employee::findOrFail($validated['employee_id']);

        $data = SalaryRecord::compute([
            'employee_id'      => $employee->id,
            'pay_period'       => $validated['pay_period'],
            'daily_rate'       => $employee->daily_rate ?? 0,
            'days_worked'      => $validated['days_worked'],
            'overtime_hours'   => $validated['overtime_hours'] ?? 0,
            'sss'              => $employee->sss ?? 0,
            'philhealth'       => $employee->philhealth ?? 0,
            'pagibig'          => $employee->pagibig ?? 0,
            'other_deductions' => $employee->other_deductions ?? 0,
            'extra_deductions' => $validated['extra_deductions'] ?? 0,
            'apply_deductions' => !empty($validated['apply_deductions']),
            'status'           => $validated['status'] ?? 'Pending',
            'notes'            => $validated['notes'] ?? null,
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
            'days_worked'      => 'required|numeric|min:0|max:5',
            'overtime_hours'   => 'nullable|numeric|min:0',
            'extra_deductions' => 'nullable|numeric|min:0',
            'apply_deductions' => 'nullable|boolean',
            'status'           => 'nullable|in:Pending,Paid',
            'notes'            => 'nullable|string|max:500',
        ]);

        $data = SalaryRecord::compute([
            'daily_rate'       => $record->daily_rate,
            'days_worked'      => $validated['days_worked'],
            'overtime_hours'   => $validated['overtime_hours'] ?? 0,
            'sss'              => $record->sss,
            'philhealth'       => $record->philhealth,
            'pagibig'          => $record->pagibig,
            'other_deductions' => $record->other_deductions,
            'extra_deductions' => $validated['extra_deductions'] ?? 0,
            'apply_deductions' => !empty($validated['apply_deductions']),
        ]);

        $record->update(array_merge($data, [
            'status' => $validated['status'] ?? $record->status,
            'notes'  => $validated['notes'] ?? $record->notes,
        ]));

        return response()->json(['record' => $this->format($record->load('employee'))]);
    }

    /** PATCH /admin/salary-records/{id}/status */
    public function updateStatus(int $id)
    {
        $record = SalaryRecord::findOrFail($id);
        $record->status = $record->status === 'Paid' ? 'Pending' : 'Paid';
        $record->save();

        return response()->json(['status' => $record->status]);
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
            'pay_period'       => $r->pay_period,
            'daily_rate'       => (float) $r->daily_rate,
            'days_worked'      => (float) $r->days_worked,
            'overtime_hours'   => (float) $r->overtime_hours,
            'sss'              => (float) $r->sss,
            'philhealth'       => (float) $r->philhealth,
            'pagibig'          => (float) $r->pagibig,
            'other_deductions' => (float) $r->other_deductions,
            'extra_deductions'  => (float) $r->extra_deductions,
            'apply_deductions'  => (bool)  $r->apply_deductions,
            'gross_pay'         => (float) $r->gross_pay,
            'total_deductions'  => (float) $r->total_deductions,
            'net_pay'           => (float) $r->net_pay,
            'status'            => $r->status,
            'notes'             => $r->notes,
        ];
    }
}
