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
            'first_name'    => ['required', 'string', 'min:2', 'max:50', 'regex:/^[A-Za-zÀ-ÖØ-öø-ÿÑñ\s\'\-]+$/u'],
            'last_name'     => ['required', 'string', 'min:2', 'max:50', 'regex:/^[A-Za-zÀ-ÖØ-öø-ÿÑñ\s\'\-]+$/u'],
            'contact'       => ['required', 'string', 'regex:/^(09|\+639)\d{9}$/'],
            'email'         => 'required|email|max:255|unique:employees,email',
            'role'          => 'required|string|in:Fabricator,Welder,Helper/Labor,Outsourced',
            'employee_type' => 'required|in:Regular,Outsourced',
            'daily_rate'    => 'required|numeric|min:1',
        ], [
            'first_name.required' => 'First name is required.',
            'first_name.min'      => 'First name must be at least 2 characters.',
            'first_name.max'      => 'First name must not exceed 50 characters.',
            'first_name.regex'    => 'First name must contain letters only (hyphens and apostrophes allowed).',
            'last_name.required'  => 'Last name is required.',
            'last_name.min'       => 'Last name must be at least 2 characters.',
            'last_name.max'       => 'Last name must not exceed 50 characters.',
            'last_name.regex'     => 'Last name must contain letters only (hyphens and apostrophes allowed).',
            'contact.required'    => 'Contact number is required.',
            'contact.regex'       => 'Must be a valid Philippine mobile number (e.g. 09171234567 or +639171234567).',
            'email.required'      => 'Email address is required.',
            'email.email'         => 'Enter a valid email address.',
            'email.max'           => 'Email must not exceed 255 characters.',
            'email.unique'        => 'An account with this email already exists.',
            'role.in'             => 'Please select a valid role.',
            'employee_type.in'    => 'Employee type must be Regular or Outsourced.',
            'daily_rate.min'      => 'Daily rate must be greater than zero.',
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
                'first_name'       => ucwords(strtolower(trim($request->first_name))),
                'last_name'        => ucwords(strtolower(trim($request->last_name))),
                'contact'          => $request->contact,
                'email'            => $request->email,
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
            'first_name'     => ['required', 'string', 'max:100', 'regex:/^[A-Za-zÀ-ÖØ-öø-ÿÑñ\s\'\-\.]+$/u'],
            'last_name'      => ['required', 'string', 'max:100', 'regex:/^[A-Za-zÀ-ÖØ-öø-ÿÑñ\s\'\-\.]+$/u'],
            'contact'        => ['required', 'string', 'regex:/^(09|\+639)\d{9}$/'],
            'email'          => 'nullable|email|unique:employees,email,' . $employee->id,
            'role'           => 'required|string|in:Fabricator,Welder,Helper/Labor,Outsourced',
            'employee_type'  => 'required|in:Regular,Outsourced',
            'daily_rate'     => 'required|numeric|min:1',
            'province'       => 'required|string|max:255',
            'city'           => 'required|string|max:255',
            'region'         => 'required|string|max:255',
            'street_address' => 'required|string|max:500',
        ], [
            'first_name.regex'    => 'First name must contain letters only (hyphens and apostrophes allowed).',
            'last_name.regex'     => 'Last name must contain letters only (hyphens and apostrophes allowed).',
            'contact.regex'       => 'Contact number must be a valid Philippine mobile number (e.g. 09171234567).',
            'email.unique'        => 'An account with this email is already used by another employee.',
            'role.in'             => 'Please select a valid role.',
            'employee_type.in'    => 'Employee type must be Regular or Outsourced.',
            'daily_rate.min'      => 'Daily rate must be greater than zero.',
            'province.required'   => 'Province is required.',
            'city.required'       => 'City / Municipality is required.',
            'region.required'     => 'Region is required.',
            'street_address.required' => 'Street address is required.',
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
            'first_name'     => ucwords(strtolower(trim($request->first_name))),
            'last_name'      => ucwords(strtolower(trim($request->last_name))),
            'contact'        => $request->contact,
            'email'          => $request->filled('email') ? $request->email : null,
            'address'        => $fullAddress,
            'region'         => ucwords(strtolower(trim($request->region))),
            'province'       => ucwords(strtolower(trim($request->province))),
            'city'           => ucwords(strtolower(trim($request->city))),
            'street_address' => ucfirst(strtolower(trim($request->street_address))),
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
