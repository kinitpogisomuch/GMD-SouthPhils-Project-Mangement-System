<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Models\Employee;

class EmployeeAccountController extends Controller
{
    private function generateUsername(): string
    {
        $row = DB::selectOne(
            "SELECT COALESCE(MAX(CAST(SUBSTRING(username FROM 6) AS INTEGER)), 0) AS max_num
             FROM employees WHERE username ~ '^EGMD-[0-9]+$'"
        );
        $nextNum = (int) ($row->max_num ?? 0) + 1;
        do {
            $username = 'EGMD-' . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
            if (!Employee::where('username', $username)->exists()) {
                return $username;
            }
            $nextNum++;
        } while (true);
    }

    private function generatePin(): string
    {
        return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name'     => 'required|string|max:100',
            'last_name'      => 'required|string|max:100',
            'contact'        => 'required|string|max:20',
            'email'          => 'nullable|email|unique:employees,email',
            'role'           => 'required|string',
            'employee_type'  => 'required|in:Regular,Outsourced',
            'daily_rate'     => 'required|numeric|min:0',
        ], [
            'email.unique' => 'An account with this email already exists.',
        ]);

        if ($validator->fails()) {
            return redirect()->route('admin.employees')
                ->withErrors($validator, 'employee_account')
                ->withInput()
                ->with('active_tab', 'employees');
        }

        $username = $this->generateUsername();
        $pin      = $this->generatePin();

        try {
            Employee::create([
                'first_name'       => $request->first_name,
                'last_name'        => $request->last_name,
                'contact'          => $request->contact,
                'email'            => $request->filled('email') ? $request->email : null,
                'role'             => $request->role,
                'employee_type'    => $request->employee_type,
                'daily_rate'       => $request->daily_rate,
                'pay_type'         => 'Daily',
                'sss'              => 0,
                'philhealth'       => 0,
                'pagibig'          => 0,
                'other_deductions' => 0,
                'status'           => 'Active',
                'username'         => $username,
                'password'         => bcrypt($pin),
                'first_login'      => true,
            ]);
        } catch (\Exception $e) {
            Log::error('EmployeeAccount: employee creation failed', ['error' => $e->getMessage()]);
            return redirect()->route('admin.employees')
                ->with(['account_error' => 'Account creation failed: ' . $e->getMessage(), 'active_tab' => 'employees']);
        }

        return redirect()->route('admin.employees')
            ->with([
                'active_tab'       => 'employees',
                'new_emp_username' => $username,
                'new_emp_pin'      => $pin,
                'new_emp_name'     => $request->last_name . ', ' . $request->first_name,
            ]);
    }

    public function update(Request $request, $id)
    {
        $employee = Employee::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'first_name'     => 'required|string|max:100',
            'last_name'      => 'required|string|max:100',
            'contact'        => 'required|string|max:20',
            'email'          => 'nullable|email',
            'role'           => 'required|string',
            'employee_type'  => 'required|in:Regular,Outsourced',
            'daily_rate'     => 'required|numeric|min:0',
            'province'       => 'required|string|max:255',
            'city'           => 'required|string|max:255',
            'region'         => 'required|string|max:255',
            'street_address' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return redirect()->route('admin.employees')
                ->withErrors($validator, 'employee_edit')
                ->withInput()
                ->with('active_tab', 'employees');
        }

        $fullAddress = implode(', ', array_filter([
            $request->street_address,
            $request->city,
            $request->province,
            $request->region,
        ]));

        $employee->update([
            'first_name'     => $request->first_name,
            'last_name'      => $request->last_name,
            'contact'        => $request->contact,
            'email'          => $request->filled('email') ? $request->email : null,
            'address'        => $fullAddress,
            'region'         => $request->region,
            'province'       => $request->province,
            'city'           => $request->city,
            'street_address' => $request->street_address,
            'role'           => $request->role,
            'employee_type'  => $request->employee_type,
            'daily_rate'     => $request->daily_rate,
        ]);

        return redirect()->route('admin.employees')
            ->with(['success' => 'Employee updated successfully!', 'active_tab' => 'employees']);
    }

    public function archive($id)
    {
        $employee = Employee::findOrFail($id);
        $restore  = ($employee->status === 'Inactive');
        $newStatus = $restore ? 'Active' : 'Inactive';

        $employee->update(['status' => $newStatus]);

        return redirect()->route('admin.employees')
            ->with([
                'success'    => $restore ? 'Employee restored successfully.' : 'Employee archived.',
                'active_tab' => 'employees',
            ]);
    }

    public function list()
    {
        $employees = Employee::where('status', 'Active')
            ->orderBy('last_name')->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'role', 'employee_type'])
            ->map(function ($e) {
                return [
                    'id'   => $e->id,
                    'name' => $e->full_name,
                    'role' => $e->role,
                    'type' => $e->employee_type,
                ];
            });

        return response()->json($employees);
    }
}
