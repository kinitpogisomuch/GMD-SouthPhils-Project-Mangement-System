<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Client;
use App\Services\NotificationService;

class ClientSignupController extends Controller
{
    public function show()
    {
        if (session('role') === 'admin')    return redirect()->route('admin.dashboard');
        if (session('role') === 'client')   return redirect()->route('client.dashboard');
        if (session('role') === 'employee') return redirect()->route('employee.dashboard');

        return view('auth.signup');
    }

    /** Suggest the next available default username (editable by the client) */
    public function nextUsername(): \Illuminate\Http\JsonResponse
    {
        return response()->json(['username' => Client::nextAvailableUsername()]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'full_name'      => 'required|string|max:255',
            'email'          => 'required|email|unique:clients,email',
            'contact_number' => 'required|string|max:20',
            'region'         => 'required|string|max:255',
            'province'       => 'required|string|max:255',
            'city'           => 'required|string|max:255',
            'barangay'       => 'required|string|max:255',
            'street_address' => 'required|string|max:500',
            'username'       => 'required|string|max:50|alpha_dash|unique:clients,username',
            'password'       => ['required', 'string', 'min:6', 'confirmed',
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
        ], [
            'email.unique'    => 'An account with this email address already exists.',
            'username.unique' => 'That username is already taken. Please choose another.',
            'username.alpha_dash' => 'Username may only contain letters, numbers, dashes, and underscores.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $fullAddress = implode(', ', array_filter([
            $request->street_address,
            $request->barangay,
            $request->city,
            $request->province,
            $request->region,
        ]));

        $client = Client::create([
            'name'           => $request->full_name,
            'email'          => $request->email,
            'contact'        => $request->contact_number,
            'address'        => $fullAddress,
            'region'         => $request->region,
            'province'       => $request->province,
            'city'           => $request->city,
            'barangay'       => $request->barangay,
            'street_address' => $request->street_address,
            'status'         => 'Pending',
            'username'       => $request->username,
            'password'       => $request->password,
            'first_login'    => false,
        ]);

        NotificationService::clientSignupPending($client);

        return redirect()->route('login')
            ->with('success', 'Your account has been created and is pending admin approval. You will be able to log in once approved.');
    }
}
