<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\SalaryRecord;
use App\Services\AuditTrail;
use App\Services\NotificationService;
use App\Services\PayMongoGateway;
use App\Services\ProjectFunds;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
            'contact'          => $employee->contact,
            'employee_type'    => $employee->employee_type ?? 'Regular',
            'profile_photo'    => $employee->profile_photo,
            'gcash_qr'         => $employee->gcash_qr,
            'pay_period'       => $payPeriod,
            'daily_rate'       => (float) ($employee->daily_rate ?? 0),
            'days_worked'      => 0,
            'half_days'        => 0,
            'overtime_hours'   => 0,
            'gross_pay'        => 0,
            'total_deductions' => 0,
            'net_pay'          => 0,
            'payment_method'   => 'cash',
            'notes'            => null,
            'status'           => null,
            'payout_ref'       => null,
        ];
    }

    /** POST /admin/salary-records */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id'    => 'required|exists:employees,id',
            'pay_period'     => 'required|date_format:Y-m-d',
            'project_ids'    => 'required|array|min:1',
            'project_ids.*'  => 'exists:projects,id',
            'days_worked'    => 'required|numeric|min:0|max:7',
            'half_days'      => 'nullable|numeric|min:0|max:7',
            'overtime_hours' => 'nullable|numeric|min:0|max:24',
            'payment_method' => 'required|in:cash,gcash',
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
            'half_days'      => $validated['half_days'] ?? 0,
            'overtime_hours' => $validated['overtime_hours'] ?? 0,
            'payment_method' => $validated['payment_method'],
            'notes'          => $validated['notes'] ?? null,
        ]);

        $existing = SalaryRecord::where('employee_id', $employee->id)->where('pay_period', $validated['pay_period'])->first();
        $pivot    = $this->buildPivotData($request->input('allocations', []), $projectIds, (float) $employee->daily_rate);

        $saved = $this->saveWithinProjectFunds($request, $existing, $pivot,
            'Labor over Total Received — ' . ($employee->full_name ?? 'employee') . ', week of ' . $validated['pay_period'],
            function () use ($employee, $validated, $data, $pivot) {
                $record = SalaryRecord::updateOrCreate(
                    ['employee_id' => $employee->id, 'pay_period' => $validated['pay_period']],
                    $data
                );
                $record->projects()->sync($pivot);
                return $record;
            });

        if (!$saved instanceof SalaryRecord) {
            return $saved;   // the "exceeds Total Received" details, or why the fund couldn't cover it
        }

        return response()->json(['record' => $this->format($saved->load('employee', 'projects'))]);
    }

    /**
     * Labor is paid from what each project's client has paid so far. Works out how much more labor this
     * save puts on each project (compared with what the record already allocated). If a project can't
     * afford it, the save only goes ahead when the admin chose to cover the difference from the revolving
     * fund (use_fund) — otherwise the "exceeds Total Received" details are returned for the modal.
     *
     * @return SalaryRecord|\Illuminate\Http\JsonResponse
     */
    private function saveWithinProjectFunds(Request $request, ?SalaryRecord $existing, array $pivot, string $purpose, callable $save)
    {
        $before = $existing
            ? DB::table('salary_record_project')->where('salary_record_id', $existing->id)->pluck('allocated_pay', 'project_id')->all()
            : [];
        $increase = [];
        foreach ($pivot as $projectId => $row) {
            $increase[$projectId] = round((float) $row['allocated_pay'] - (float) ($before[$projectId] ?? 0), 2);
        }

        $useFund = $request->boolean('use_fund');
        if (!$useFund && ($overruns = ProjectFunds::overruns($increase))) {
            return response()->json([
                'message'      => 'This salary is more than the money received for ' . (count($overruns) === 1 ? 'this project' : 'these projects') . '.',
                'fund_overrun' => ProjectFunds::overrunPayload($overruns),
            ], 422);
        }

        try {
            return DB::transaction(function () use ($useFund, $increase, $purpose, $save, $existing) {
                if ($useFund) {
                    ProjectFunds::coverOverruns($increase, $purpose, 'labor', $existing ? 'salary_record:' . $existing->id : null);
                }
                return $save();
            });
        } catch (\DomainException $e) {
            return response()->json(['errors' => ['fund' => [$e->getMessage()]]], 422);
        }
    }

    /** PUT /admin/salary-records/{id} */
    public function update(Request $request, int $id)
    {
        $record = SalaryRecord::findOrFail($id);

        $validated = $request->validate([
            'days_worked'    => 'required|numeric|min:0|max:7',
            'half_days'      => 'nullable|numeric|min:0|max:7',
            'overtime_hours' => 'nullable|numeric|min:0|max:24',
            'payment_method' => 'required|in:cash,gcash',
            'project_ids'    => 'required|array|min:1',
            'project_ids.*'  => 'exists:projects,id',
            'notes'          => 'nullable|string|max:500',
        ]);

        $projectIds = array_filter((array) $request->input('project_ids', []));

        $data = SalaryRecord::compute([
            'daily_rate'     => (float) $record->daily_rate,
            'days_worked'    => $validated['days_worked'],
            'half_days'      => $validated['half_days'] ?? 0,
            'overtime_hours' => $validated['overtime_hours'] ?? 0,
        ]);

        $pivot = $this->buildPivotData($request->input('allocations', []), $projectIds, (float) $record->daily_rate);

        $saved = $this->saveWithinProjectFunds($request, $record, $pivot,
            'Labor over Total Received — ' . ($record->employee->full_name ?? 'employee') . ', week of ' . $record->pay_period,
            function () use ($record, $data, $projectIds, $validated, $pivot) {
                $record->update(array_merge($data, [
                    'project_id'     => count($projectIds) === 1 ? $projectIds[0] : null,
                    'payment_method' => $validated['payment_method'],
                    'notes'          => $validated['notes'] ?? $record->notes,
                ]));
                $record->projects()->sync($pivot);
                return $record;
            });

        if (!$saved instanceof SalaryRecord) {
            return $saved;
        }

        return response()->json(['record' => $this->format($saved->load('employee', 'projects'))]);
    }

    /**
     * POST /admin/salary-records/pay-now
     * "Pay Now" in Record Salary. Nothing is saved yet: the salary is checked with a trial save that is rolled
     * back (same rules and project-fund check as Save), the admin is sent to PayMongo's checkout page — the same
     * way clients pay — and the record is only written once PayMongo confirms the payment (payNowReturn).
     * A cancelled or unfinished payment leaves no record. Test mode only: a checkout collects money into GMD's
     * own PayMongo account, so in live mode it would not reach the employee; real payouts need Disbursements.
     */
    public function payNow(Request $request, PayMongoGateway $gateway)
    {
        $recordId = $request->input('record_id') ? (int) $request->input('record_id') : null;
        $existing = $recordId
            ? SalaryRecord::findOrFail($recordId)
            : SalaryRecord::where('employee_id', $request->input('employee_id'))->where('pay_period', $request->input('pay_period'))->first();
        if ($existing && $existing->status === 'Paid') {
            return response()->json(['message' => 'This week is already paid.'], 422);
        }

        // trial save: validates, computes the pay and runs the project-fund check — then undone
        DB::beginTransaction();
        try {
            $trial = $recordId ? $this->update($request, $recordId) : $this->store($request);
        } finally {
            DB::rollBack();
        }
        if ($trial->getStatusCode() !== 200) {
            return $trial;   // validation errors, or the "exceeds Total Received" details for the fund modal
        }

        $preview = $trial->getData(true)['record'];
        if ((float) $preview['net_pay'] < 20) {
            return response()->json(['message' => 'There is nothing to pay — enter the days worked (PayMongo needs at least ₱20.00).'], 422);
        }
        if (!$gateway->isConfigured()) {
            return response()->json(['message' => 'PayMongo is not set up yet.'], 422);
        }
        if (!$gateway->isTestMode()) {
            return response()->json(['message' => 'Salary checkout only runs with PayMongo test keys. Real salary payouts need PayMongo Disbursements.'], 422);
        }

        $employee  = Employee::find($preview['employee_id']);
        $token     = Str::random(32);
        $reference = 'SAL-' . strtoupper(Str::random(8));
        $week      = \Carbon\Carbon::parse($preview['pay_period']);
        $weekText  = $week->format('M j') . ' – ' . $week->copy()->addDays(6)->format('M j, Y');
        $number    = $this->mobileNumber($employee->contact ?? null);

        try {
            $session = $gateway->createCheckoutSession([
                'amount'      => (float) $preview['net_pay'],
                'name'        => 'Salary — ' . ($employee->full_name ?? 'Employee'),
                'description' => 'GMD South Phils salary · week of ' . $weekText,
                'reference'   => $reference,
                'success_url' => route('admin.salary.pay_now_return', $token),
                'cancel_url'  => route('admin.salary.pay_now_cancel', $token),
                'metadata'    => ['salary_employee_id' => (string) $preview['employee_id'], 'pay_period' => $preview['pay_period']],
            ]);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        // what to save once paid — kept in the admin's session until PayMongo sends them back
        session()->put('salary_pay_now.' . $token, [
            'record_id'           => $recordId,
            'input'               => $request->only(['employee_id', 'pay_period', 'project_ids', 'days_worked', 'half_days', 'overtime_hours', 'payment_method', 'notes', 'allocations', 'use_fund']),
            'pay_period'          => $preview['pay_period'],
            'reference'           => $reference,
            'checkout_session_id' => $session['id'] ?? null,
            'amount'              => (float) $preview['net_pay'],
            'destination'         => $number ? 'GCash •••• ' . substr($number, -4) : 'GCash',
            'account_name'        => $employee->full_name ?? 'Employee',
        ]);

        return response()->json(['checkout_url' => $session['attributes']['checkout_url'] ?? null]);
    }

    /** GET /admin/salary-pay-now/{token}/return — back from PayMongo: save the salary only if it was paid */
    public function payNowReturn(string $token, PayMongoGateway $gateway)
    {
        $pending = session()->pull('salary_pay_now.' . $token);   // one use: opening the page again does nothing
        if (!$pending) {
            return $this->backToSalary(null, 'This payment was already handled.');
        }

        try {
            $session = $gateway->retrieveCheckoutSession($pending['checkout_session_id']);
        } catch (\RuntimeException $e) {
            report($e);
            session()->put('salary_pay_now.' . $token, $pending);   // let them come back and try again
            return $this->backToSalary($pending['pay_period'], 'Could not confirm the payment with PayMongo. Please reload this page.');
        }

        $attrs = $session['attributes'] ?? [];
        $paid  = collect($attrs['payments'] ?? [])->first(fn ($p) => ($p['attributes']['status'] ?? null) === 'paid');
        if (!$paid) {
            $gateway->expireCheckoutSession($pending['checkout_session_id']);
            return $this->backToSalary($pending['pay_period'], 'The payment was not completed, so nothing was saved.');
        }

        // paid — now save it, exactly as Save would have
        $request = Request::create('/admin/salary-records', 'POST', $pending['input']);
        $request->headers->set('Accept', 'application/json');
        try {
            $saved = $pending['record_id'] ? $this->update($request, (int) $pending['record_id']) : $this->store($request);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $saved = response()->json(['errors' => $e->errors()], 422);
        }
        $record = $saved->getStatusCode() === 200 ? SalaryRecord::find($saved->getData(true)['record']['id'] ?? 0) : null;
        if (!$record) {
            report(new \RuntimeException('Salary paid through PayMongo but could not be saved: ' . json_encode($saved->getData(true)) . ' ref ' . $pending['reference']));
            return $this->backToSalary($pending['pay_period'], 'The payment went through (reference ' . $pending['reference'] . '), but the salary could not be saved. Please record it again.');
        }

        $this->markSalaryPaid($record, $pending, $paid['id'] ?? null, $attrs['payment_method_used'] ?? null);

        return $this->backToSalary($pending['pay_period'], 'Salary paid through PayMongo — reference ' . $pending['reference'] . '.');
    }

    /** GET /admin/salary-pay-now/{token}/cancel — left PayMongo without paying: nothing is saved */
    public function payNowCancel(string $token, PayMongoGateway $gateway)
    {
        $pending = session()->pull('salary_pay_now.' . $token);
        if ($pending && !empty($pending['checkout_session_id'])) {
            $gateway->expireCheckoutSession($pending['checkout_session_id']);
        }

        return $this->backToSalary($pending['pay_period'] ?? null, 'Payment cancelled — nothing was saved.');
    }

    /** Back to Employees → Salary, on the week that was being paid */
    private function backToSalary(?string $payPeriod, string $message)
    {
        return redirect()->route('admin.employees')->with(array_filter([
            'active_tab'    => 'salary',
            'salary_period' => $payPeriod,
            'emp_toast'     => $message,
        ]));
    }

    /** "0917 123 4567", "+63 917…" or "63917…" → "09171234567"; null when it isn't a PH mobile number */
    private function mobileNumber(?string $raw): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $raw);
        if (str_starts_with($digits, '63') && strlen($digits) === 12) {
            $digits = '0' . substr($digits, 2);
        }
        return preg_match('/^09\d{9}$/', $digits) ? $digits : null;
    }

    /** Paid through PayMongo: mark the record, log it and tell the employee — once */
    private function markSalaryPaid(SalaryRecord $record, array $started, ?string $paymentId, ?string $method): void
    {
        $updated = SalaryRecord::whereKey($record->id)->where('status', '!=', 'Paid')
            ->update(['status' => 'Paid', 'payment_method' => 'gcash', 'updated_at' => now()]);
        if (!$updated) {
            return;   // already marked paid (e.g. the return page was opened twice)
        }

        AuditTrail::record('salary_record', $record->id, 'salary.payout_paid', [
            'reference'           => $started['reference'],
            'provider'            => 'PayMongo (test mode)',
            'checkout_session_id' => $started['checkout_session_id'],
            'payment_id'          => $paymentId,
            'method_used'         => $method,
            'amount'              => (float) $record->net_pay,
            'destination'         => $started['destination'] ?? null,
            'account_name'        => $started['account_name'] ?? null,
        ]);

        $week = \Carbon\Carbon::parse($record->pay_period);
        NotificationService::notifyEmployee(
            $record->employee_id,
            'Salary Sent',
            'Your pay of ₱' . number_format((float) $record->net_pay, 2) . ' for the week of ' . $week->format('M j') . ' – ' . $week->copy()->addDays(6)->format('M j, Y')
                . ' was sent to your ' . ($started['destination'] ?? 'GCash') . ' through PayMongo. Reference ' . $started['reference'] . '. (Test mode — no real money was sent.)',
            'salary_paid',
            'info',
            null,
            null,
            '/employee/salary'
        );
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
            'contact'          => $r->employee->contact ?? null,
            'employee_type'    => $r->employee->employee_type ?? 'Regular',
            'profile_photo'    => $r->employee->profile_photo ?? null,
            'gcash_qr'         => $r->employee->gcash_qr ?? null,
            'pay_period'       => $r->pay_period,
            'daily_rate'       => $dailyRate,          // snapshot — not current employee rate
            'days_worked'      => $daysWorked,
            'half_days'        => (float) $r->half_days,
            'overtime_hours'   => $overtimeHours,
            'overtime_pay'     => round($overtimePay, 2),
            'gross_pay'        => (float) $r->gross_pay,
            'total_deductions' => (float) $r->total_deductions,
            'net_pay'          => (float) $r->net_pay,
            'payment_method'   => $r->payment_method ?? 'cash',
            'notes'            => $r->notes,
            'status'           => $r->status ?? 'Pending',
            // reference of the PayMongo payout (test mode), when one was made
            'payout_ref'       => $r->status === 'Paid'
                ? (AuditLog::where('auditable_type', 'salary_record')->where('auditable_id', (string) $r->id)
                    ->where('action', 'salary.payout_paid')->latest('id')->value('details')['reference'] ?? null)
                : null,
            'project_ids'      => $r->relationLoaded('projects')
                ? $r->projects->pluck('id')->toArray()
                : ($r->project_id ? [$r->project_id] : []),
            // What this record already puts on each project — editing it only counts the increase
            // against the project's Total Received
            'allocations'      => $r->relationLoaded('projects')
                ? $r->projects->mapWithKeys(fn ($p) => [$p->id => (float) $p->pivot->allocated_pay])->all()
                : [],
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
                $funds = ProjectFunds::summary($p->id);
                return [
                    'id'     => $p->id,
                    'code'   => $p->code,
                    'name'   => $p->name,
                    'client' => $p->client,
                    'spec'   => $spec ?: ($p->tank_type ?? ''),
                    // what the project has received and spent, for the "Available" panel in the salary form
                    'funds'  => [
                        'received'  => $funds['received'],
                        'spent'     => $funds['spent'],
                        'available' => $funds['available'],
                        'fund_owed' => $funds['fund_owed'],
                    ],
                ];
            });
        return response()->json(['projects' => $projects, 'fund_balance' => \App\Models\FundSetting::getCurrentBalance()]);
    }
}
