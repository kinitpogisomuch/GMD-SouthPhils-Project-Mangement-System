<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\Employee;
use App\Models\Client;

class FirstLoginController extends Controller
{
    private function findAuthRecord(string $role, int $id): User|Employee|Client|null
    {
        return match ($role) {
            'employee' => Employee::find($id),
            'client'   => Client::find($id),
            default    => User::find($id),
        };
    }

    public function show()
    {
        if (!session('user_id')) {
            return redirect()->route('login');
        }

        if (session('first_login') !== true) {
            $role = session('role');
            return redirect()->route($role . '.dashboard');
        }

        $user = $this->findAuthRecord(session('role'), session('user_id'));
        if (!$user) return redirect()->route('login');

        return view('auth.first_login_setup', compact('user'));
    }

    public function handle(Request $request)
    {
        if (!session('user_id')) {
            return redirect()->route('login');
        }

        $userId = session('user_id');
        $role   = session('role');
        $user   = $this->findAuthRecord($role, $userId);

        if (!$user) return redirect()->route('login');

        $table = match ($role) {
            'employee' => 'employees',
            'client'   => 'clients',
            default    => 'users',
        };

        $validator = Validator::make($request->all(), [
            'email'                    => "required|email|unique:{$table},email,{$userId}",
            'region'                   => 'required|string|max:255',
            'province'                 => 'required|string|max:255',
            'city'                     => 'required|string|max:255',
            'barangay'                 => 'required|string|max:255',
            'street_address'           => 'required|string|max:500',
            'current_pin'              => 'required|string',
            'new_password'             => ['required', 'string', 'min:6', 'confirmed',
                function ($_, $value, $fail) {
                    if (!preg_match('/[A-Z]/', $value)) {
                        $fail('Password must contain at least one uppercase letter.');
                    }
                    if (!preg_match('/[a-z]/', $value)) {
                        $fail('Password must contain at least one lowercase letter.');
                    }
                    if (!preg_match('/[0-9]/', $value)) {
                        $fail('Password must contain at least one number.');
                    }
                },
            ],
            'new_password_confirmation' => 'required|string',
        ], [
            'new_password.confirmed' => 'Password confirmation does not match.',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Verify current PIN
        if (!Hash::check($request->current_pin, $user->password)) {
            return back()
                ->withErrors(['current_pin' => 'The current PIN you entered is incorrect.'])
                ->withInput();
        }

        // New password must differ from current PIN
        if (Hash::check($request->new_password, $user->password)) {
            return back()
                ->withErrors(['new_password' => 'New password cannot be the same as your current temporary PIN.'])
                ->withInput();
        }

        $fullAddress = implode(', ', array_filter([
            $request->street_address,
            $request->barangay,
            $request->city,
            $request->province,
            $request->region,
        ]));

        $user->update([
            'email'               => $request->email,
            'region'              => $request->region,
            'province'            => $request->province,
            'city'                => $request->city,
            'barangay'            => $request->barangay,
            'street_address'      => $request->street_address,
            'address'             => $fullAddress,
            'password'            => $request->new_password,
            'first_login'         => false,
            'password_updated_at' => now(),
        ]);

        session([
            'first_login' => false,
            'email'       => $request->email,
        ]);

        $role = session('role');
        return redirect()->route($role . '.dashboard')
            ->with('success', 'Account setup complete! Welcome to the system.');
    }
}
