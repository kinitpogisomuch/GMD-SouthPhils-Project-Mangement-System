<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\Employee;
use App\Models\Client;
use App\Services\SupabaseStorageService;

class ProfileController extends Controller
{
    // =========================================================================
    // ADMIN
    // =========================================================================
    public function updateAdmin(Request $request)
    {
        $admin = User::findOrFail(session('user_id'));

        $validator = Validator::make($request->all(), [
            'first_name'     => ['required', 'string', 'max:100', 'regex:/^[^0-9]+$/u'],
            'last_name'      => ['nullable', 'string', 'max:100', 'regex:/^[^0-9]*$/u'],
            'email'          => 'required|email|max:255|unique:users,email,' . $admin->id,
            'phone'          => ['nullable', 'string', 'digits:11'],
            'region'         => 'nullable|string|max:255',
            'province'       => 'nullable|string|max:255',
            'city'           => 'nullable|string|max:255',
            'street_address' => 'nullable|string|max:500',
        ], [
            'first_name.regex'  => 'First name must not contain numbers.',
            'last_name.regex'   => 'Last name must not contain numbers.',
            'email.email'       => 'Enter a valid email address.',
            'email.unique'      => 'This email is already in use.',
            'phone.digits'      => 'Phone number must be exactly 11 digits.',
        ]);

        if ($validator->fails()) {
            return redirect()->route('admin.settings')
                ->withErrors($validator, 'profile')
                ->withInput()
                ->with('active_tab', 'profile');
        }

        $fullName = trim($request->last_name)
            ? trim($request->last_name . ', ' . $request->first_name)
            : trim($request->first_name);

        $admin->update([
            'full_name' => $fullName,
            'email'     => $request->email,
            'phone'     => $request->phone,
        ]);

        session(['full_name' => $fullName, 'name' => $fullName, 'email' => $request->email]);

        return redirect()->route('admin.settings')
            ->with(['success' => 'Profile updated successfully.', 'active_tab' => 'profile']);
    }

    public function updateAdminPassword(Request $request)
    {
        $admin = User::findOrFail(session('user_id'));

        [$errors, $validated] = $this->validatePasswordChange($request, $admin);
        if ($errors) {
            return redirect()->route('admin.settings')
                ->withErrors($errors, 'password')
                ->with('active_tab', 'password');
        }

        $admin->update(['password' => $request->new_password, 'password_updated_at' => now()]);

        return redirect()->route('admin.settings')
            ->with(['success' => 'Password updated successfully.', 'active_tab' => 'password']);
    }

    // =========================================================================
    // EMPLOYEE
    // =========================================================================
    public function updateEmployee(Request $request)
    {
        $employee = Employee::findOrFail(session('user_id'));

        $validator = Validator::make($request->all(), [
            'first_name'     => 'required|string|max:100',
            'last_name'      => 'required|string|max:100',
            'email'          => 'required|email|unique:employees,email,' . $employee->id,
            'contact'        => 'nullable|string|max:20',
            'region'         => 'nullable|string|max:255',
            'province'       => 'nullable|string|max:255',
            'city'           => 'nullable|string|max:255',
            'street_address' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return redirect()->route('employee.settings')
                ->withErrors($validator, 'profile')
                ->withInput();
        }

        $fullAddress = $this->buildAddress($request);

        $employee->update([
            'first_name'     => $request->first_name,
            'last_name'      => $request->last_name,
            'email'          => $request->email,
            'contact'        => $request->contact,
            'region'         => $request->region,
            'province'       => $request->province,
            'city'           => $request->city,
            'street_address' => $request->street_address,
            'address'        => $fullAddress,
        ]);

        $fullName = trim($request->last_name . ', ' . $request->first_name);
        session([
            'full_name' => $fullName,
            'name'      => trim($request->first_name . ' ' . $request->last_name),
            'email'     => $request->email,
        ]);

        return redirect()->route('employee.settings')
            ->with('success', 'Profile updated successfully.');
    }

    public function updateEmployeePassword(Request $request)
    {
        $employee = Employee::findOrFail(session('user_id'));

        [$errors, $validated] = $this->validatePasswordChange($request, $employee);
        if ($errors) {
            return redirect()->route('employee.settings')
                ->withErrors($errors, 'password');
        }

        $employee->update(['password' => $request->new_password, 'password_updated_at' => now()]);

        return redirect()->route('employee.settings')
            ->with('success', 'Password updated successfully.');
    }

    // =========================================================================
    // CLIENT
    // =========================================================================
    public function updateClient(Request $request)
    {
        $client = Client::findOrFail(session('user_id'));

        $validator = Validator::make($request->all(), [
            'full_name'      => 'required|string|max:255',
            'email'          => 'required|email|unique:clients,email,' . $client->id,
            'contact'        => 'nullable|string|max:20',
            'region'         => 'nullable|string|max:255',
            'province'       => 'nullable|string|max:255',
            'city'           => 'nullable|string|max:255',
            'street_address' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return redirect()->route('client.settings')
                ->withErrors($validator, 'profile')
                ->withInput();
        }

        $fullName    = trim($request->full_name);
        $fullAddress = $this->buildAddress($request);

        $client->update([
            'name'           => $fullName,
            'first_name'     => null,
            'last_name'      => null,
            'email'          => $request->email,
            'contact'        => $request->contact,
            'region'         => $request->region,
            'province'       => $request->province,
            'city'           => $request->city,
            'street_address' => $request->street_address,
            'address'        => $fullAddress,
        ]);

        session([
            'full_name' => $fullName,
            'name'      => $fullName,
            'email'     => $request->email,
        ]);

        return redirect()->route('client.settings')
            ->with('success', 'Profile updated successfully.');
    }

    public function updateClientPassword(Request $request)
    {
        $client = Client::findOrFail(session('user_id'));

        [$errors, $validated] = $this->validatePasswordChange($request, $client);
        if ($errors) {
            return redirect()->route('client.settings')
                ->withErrors($errors, 'password');
        }

        $client->update(['password' => $request->new_password, 'password_updated_at' => now()]);

        return redirect()->route('client.settings')
            ->with('success', 'Password updated successfully.');
    }

    // =========================================================================
    // Shared helpers
    // =========================================================================
    private function buildAddress(Request $request): ?string
    {
        $parts = array_filter([
            $request->street_address,
            $request->city,
            $request->province,
            $request->region,
        ]);
        return $parts ? implode(', ', $parts) : null;
    }

    private function validatePasswordChange(Request $request, $user): array
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password'     => [
                'required', 'string', 'min:6', 'confirmed',
                function ($_, $value, $fail) {
                    if (!preg_match('/[A-Z]/', $value)) $fail('Password must contain at least one uppercase letter.');
                    if (!preg_match('/[a-z]/', $value)) $fail('Password must contain at least one lowercase letter.');
                    if (!preg_match('/[0-9]/', $value)) $fail('Password must contain at least one number.');
                },
            ],
            'new_password_confirmation' => 'required|string',
        ], ['new_password.confirmed' => 'Passwords do not match.']);

        if ($validator->fails()) {
            return [$validator, null];
        }

        if (!Hash::check($request->current_password, $user->password)) {
            $validator->errors()->add('current_password', 'The current password you entered is incorrect.');
            return [$validator, null];
        }

        if (Hash::check($request->new_password, $user->password)) {
            $validator->errors()->add('new_password', 'New password cannot be the same as your current password.');
            return [$validator, null];
        }

        return [null, true];
    }

    // =========================================================================
    // PHOTO UPLOAD — shared logic
    // =========================================================================
    private function uploadPhoto(Request $request, $user, string $folder): ?string
    {
        $request->validate([
            'profile_photo' => 'required|image|mimes:jpeg,jpg,png,webp|max:4096',
        ]);

        $storage = app(SupabaseStorageService::class);
        $url     = $storage->upload($request->file('profile_photo'), $folder);

        if ($url) {
            $user->update(['profile_photo' => $url]);
        }

        return $url;
    }

    public function uploadAdminPhoto(Request $request)
    {
        $admin = User::findOrFail(session('user_id'));
        $url   = $this->uploadPhoto($request, $admin, 'avatars/admin/' . $admin->id);

        if ($url) {
            session(['profile_photo' => $url]);
        }

        return redirect()->route('admin.settings')
            ->with(['success' => 'Profile photo updated.', 'active_tab' => 'profile']);
    }

    public function uploadEmployeePhoto(Request $request)
    {
        $employee = Employee::findOrFail(session('user_id'));
        $url      = $this->uploadPhoto($request, $employee, 'avatars/employee/' . $employee->id);

        if ($url) {
            session(['profile_photo' => $url]);
        }

        return redirect()->route('employee.settings')
            ->with('success', 'Profile photo updated.');
    }

    public function uploadClientPhoto(Request $request)
    {
        $client = Client::findOrFail(session('user_id'));
        $url    = $this->uploadPhoto($request, $client, 'avatars/client/' . $client->id);

        if ($url) {
            session(['profile_photo' => $url]);
        }

        return redirect()->route('client.settings')
            ->with('success', 'Profile photo updated.');
    }
}
