<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function index()
    {
        if (session('role') === 'admin')    return redirect()->route('admin.dashboard');
        if (session('role') === 'client')   return redirect()->route('client.dashboard');
        if (session('role') === 'employee') return redirect()->route('employee.dashboard');

        return view('landing');
    }

    public function showLogin()
    {
        if (session('role') === 'admin') {
            return redirect()->route('admin.dashboard');
        }
        if (session('role') === 'client') {
            return redirect()->route('client.dashboard');
        }
        if (session('role') === 'employee') {
            return redirect()->route('employee.dashboard');
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $username = trim($request->input('email'));
        $password = trim($request->input('password'));

        // 1. Admin — check users table
        $user = DB::table('users')
            ->where(function ($q) use ($username) {
                $q->where('username', $username)->orWhere('email', $username);
            })
            ->first();

        if ($user && Hash::check($password, $user->password)) {
            if ($user->status !== 'Active') {
                return redirect()->route('login')
                    ->with('error', 'Your account is inactive. Please contact the administrator.')
                    ->withInput(['email' => $username]);
            }
            $request->session()->flush();
            session([
                'user_id'       => $user->id,
                'full_name'     => $user->full_name,
                'name'          => $user->full_name,
                'role'          => $user->role,
                'email'         => $user->email,
                'first_login'   => (bool) $user->first_login,
                'profile_photo' => $user->profile_photo,
            ]);
            if ($user->first_login) return redirect()->route('setup.credentials');
            return redirect()->route('admin.dashboard');
        }

        // 2. Employee — check employees table
        $employee = DB::table('employees')
            ->whereNotNull('username')
            ->where(function ($q) use ($username) {
                $q->where('username', $username)->orWhere('email', $username);
            })
            ->first();

        if ($employee && $employee->password && Hash::check($password, $employee->password)) {
            if ($employee->status !== 'Active') {
                return redirect()->route('login')
                    ->with('error', 'Your account is inactive. Please contact the administrator.')
                    ->withInput(['email' => $username]);
            }
            $request->session()->flush();
            session([
                'user_id'       => $employee->id,
                'full_name'     => trim($employee->last_name . ', ' . $employee->first_name),
                'name'          => trim($employee->first_name . ' ' . $employee->last_name),
                'role'          => 'employee',
                'email'         => $employee->email,
                'first_login'   => (bool) $employee->first_login,
                'profile_photo' => $employee->profile_photo,
            ]);
            if ($employee->first_login) return redirect()->route('setup.credentials');
            return redirect()->route('employee.dashboard');
        }

        // 3. Client — check clients table
        $client = DB::table('clients')
            ->whereNotNull('username')
            ->where(function ($q) use ($username) {
                $q->where('username', $username)->orWhere('email', $username);
            })
            ->first();

        if ($client && $client->password && Hash::check($password, $client->password)) {
            if ($client->status !== 'Active') {
                return redirect()->route('login')
                    ->with('error', 'Your account is inactive. Please contact the administrator.')
                    ->withInput(['email' => $username]);
            }
            $request->session()->flush();
            session([
                'user_id'       => $client->id,
                'full_name'     => $client->name,
                'name'          => $client->name,
                'role'          => 'client',
                'email'         => $client->email,
                'first_login'   => (bool) $client->first_login,
                'profile_photo' => $client->profile_photo,
            ]);
            if ($client->first_login) return redirect()->route('setup.credentials');
            return redirect()->route('client.dashboard');
        }

        return redirect()->route('login')
            ->with('error', 'Invalid credentials. Please check your username and password.')
            ->withInput(['email' => $username]);
    }

    public function logout(Request $request)
    {
        $request->session()->flush();
        $request->session()->regenerate();
        return redirect()->route('home');
    }
}