<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use App\Models\PasswordReset;
use App\Models\User;
use App\Models\Employee;
use App\Models\Client;

class ForgotPasswordController extends Controller
{
    // -------------------------------------------------------------------------
    // Step 1 — show email form
    // -------------------------------------------------------------------------
    public function showEmailForm()
    {
        return view('auth.forgot_password');
    }

    // -------------------------------------------------------------------------
    // Step 1 POST — look up email, generate code, send email
    // -------------------------------------------------------------------------
    public function sendCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $email = strtolower(trim($request->email));

        [$userType, $userId] = $this->findUserByEmail($email);

        if (!$userType) {
            return back()
                ->withErrors(['email' => 'No account is associated with this email address.'])
                ->withInput();
        }

        // Enforce 60-second resend cooldown
        $recent = PasswordReset::where('email', $email)
            ->whereRaw('"is_used" = false')
            ->where('expires_at', '>', now())
            ->where('created_at', '>', now()->subSeconds(60))
            ->latest()
            ->first();

        if ($recent) {
            $wait = max(1, 60 - (int) now()->diffInSeconds($recent->created_at));
            return back()
                ->withErrors(['email' => "Please wait {$wait} second(s) before requesting a new code."])
                ->withInput();
        }

        // Invalidate any previous codes for this email
        PasswordReset::where('email', $email)->update(['is_used' => DB::raw('true')]);

        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        PasswordReset::create([
            'email'      => $email,
            'code_hash'  => Hash::make($code),
            'expires_at' => now()->addMinutes(10),
            'is_used'    => DB::raw('false'),
        ]);

        try {
            Mail::html(
                $this->buildCodeEmail($code),
                fn ($m) => $m->to($email)
                    ->from(config('mail.from.address'), config('mail.from.name'))
                    ->replyTo(config('mail.from.address'), config('mail.from.name'))
                    ->subject('GMD Password Reset Verification Code')
            );
        } catch (\Exception $e) {
            return back()
                ->withErrors(['email' => 'Failed to send email. Please try again later.'])
                ->withInput();
        }

        session([
            'fp_email'     => $email,
            'fp_user_type' => $userType,
            'fp_user_id'   => $userId,
            'fp_verified'  => false,
        ]);

        return redirect()->route('password.verify')
            ->with('code_sent', true);
    }

    // -------------------------------------------------------------------------
    // Step 2 — show code verification form
    // -------------------------------------------------------------------------
    public function showVerifyForm()
    {
        if (!session('fp_email')) {
            return redirect()->route('password.request');
        }

        return view('auth.forgot_verify');
    }

    // -------------------------------------------------------------------------
    // Step 2 POST — verify the 6-digit code
    // -------------------------------------------------------------------------
    public function verifyCode(Request $request)
    {
        if (!session('fp_email')) {
            return redirect()->route('password.request');
        }

        $request->validate([
            'code' => 'required|string',
        ]);

        $email = session('fp_email');

        $reset = PasswordReset::where('email', $email)
            ->whereRaw('"is_used" = false')
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (!$reset) {
            return back()->withErrors(['code' => 'This verification code has expired. Please request a new code.']);
        }

        if (!Hash::check(trim($request->code), $reset->code_hash)) {
            return back()->withErrors(['code' => 'The verification code you entered is incorrect.']);
        }

        // Mark code consumed and advance to reset step
        $reset->update(['is_used' => DB::raw('true')]);
        session(['fp_verified' => true]);

        return redirect()->route('password.reset.form');
    }

    // -------------------------------------------------------------------------
    // Resend code
    // -------------------------------------------------------------------------
    public function resendCode(Request $request)
    {
        if (!session('fp_email')) {
            return redirect()->route('password.request');
        }

        $email = session('fp_email');

        $recent = PasswordReset::where('email', $email)
            ->whereRaw('"is_used" = false')
            ->where('expires_at', '>', now())
            ->where('created_at', '>', now()->subSeconds(60))
            ->latest()
            ->first();

        if ($recent) {
            $wait = max(1, 60 - (int) now()->diffInSeconds($recent->created_at));
            return back()->withErrors(['code' => "Please wait {$wait} second(s) before resending."]);
        }

        PasswordReset::where('email', $email)->update(['is_used' => DB::raw('true')]);

        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        PasswordReset::create([
            'email'      => $email,
            'code_hash'  => Hash::make($code),
            'expires_at' => now()->addMinutes(10),
            'is_used'    => DB::raw('false'),
        ]);

        try {
            Mail::html(
                $this->buildCodeEmail($code),
                fn ($m) => $m->to($email)
                    ->from(config('mail.from.address'), config('mail.from.name'))
                    ->replyTo(config('mail.from.address'), config('mail.from.name'))
                    ->subject('GMD Password Reset Verification Code')
            );
        } catch (\Exception $e) {
            return back()->withErrors(['code' => 'Failed to send email. Please try again.']);
        }

        return back()->with('resent', true);
    }

    // -------------------------------------------------------------------------
    // Step 3 — show new password form
    // -------------------------------------------------------------------------
    public function showResetForm()
    {
        if (!session('fp_email') || !session('fp_verified')) {
            return redirect()->route('password.request');
        }

        return view('auth.forgot_reset');
    }

    // -------------------------------------------------------------------------
    // Step 3 POST — update password + auto-login
    // -------------------------------------------------------------------------
    public function resetPassword(Request $request)
    {
        if (!session('fp_email') || !session('fp_verified')) {
            return redirect()->route('password.request');
        }

        $validator = Validator::make($request->all(), [
            'password' => [
                'required', 'string', 'min:6', 'confirmed',
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
            'password_confirmation' => 'required|string',
        ], [
            'password.confirmed' => 'Passwords do not match.',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $userType = session('fp_user_type');
        $userId   = session('fp_user_id');

        $user = match ($userType) {
            'admin'    => User::find($userId),
            'employee' => Employee::find($userId),
            'client'   => Client::find($userId),
            default    => null,
        };

        if (!$user) {
            return redirect()->route('password.request')
                ->withErrors(['email' => 'Account not found. Please start again.']);
        }

        if (Hash::check($request->password, $user->password)) {
            return back()->withErrors(['password' => 'New password cannot be the same as your current password.']);
        }

        $user->update([
            'password'            => $request->password,
            'password_updated_at' => now(),
        ]);

        // Invalidate all reset codes for this email
        PasswordReset::where('email', session('fp_email'))->update(['is_used' => DB::raw('true')]);

        // Wipe forgot-password session keys, then build the login session
        $email = session('fp_email');
        $request->session()->flush();

        if ($userType === 'admin') {
            session([
                'user_id'     => $user->id,
                'full_name'   => $user->full_name,
                'name'        => $user->full_name,
                'role'        => 'admin',
                'email'       => $user->email,
                'first_login' => (bool) $user->first_login,
            ]);
        } elseif ($userType === 'employee') {
            session([
                'user_id'     => $user->id,
                'full_name'   => trim($user->last_name . ', ' . $user->first_name),
                'name'        => trim($user->first_name . ' ' . $user->last_name),
                'role'        => 'employee',
                'email'       => $user->email,
                'first_login' => (bool) $user->first_login,
            ]);
        } else {
            session([
                'user_id'     => $user->id,
                'full_name'   => $user->full_name ?? $user->name,
                'name'        => $user->full_name ?? $user->name,
                'role'        => 'client',
                'email'       => $user->email,
                'first_login' => (bool) $user->first_login,
            ]);
        }

        return redirect()->route($userType . '.dashboard')
            ->with('success', 'Password updated successfully. Welcome back!');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------
    private function findUserByEmail(string $email): array
    {
        $admin = DB::table('users')->where('email', $email)->first();
        if ($admin) return ['admin', $admin->id];

        $emp = DB::table('employees')
            ->where('email', $email)
            ->whereNotNull('username')
            ->first();
        if ($emp) return ['employee', $emp->id];

        $client = DB::table('clients')
            ->where('email', $email)
            ->whereNotNull('username')
            ->first();
        if ($client) return ['client', $client->id];

        return [null, null];
    }

    private function buildCodeEmail(string $code): string
    {
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 20px; }
                .container { background: #fff; max-width: 480px; margin: 0 auto; border-radius: 12px; padding: 36px; box-shadow: 0 2px 12px rgba(0,0,0,0.07); }
                .logo { font-size: 18px; font-weight: 900; color: #333; margin-bottom: 24px; letter-spacing: -0.5px; }
                h2 { font-size: 18px; color: #111; margin-bottom: 8px; }
                p { font-size: 14px; color: #555; line-height: 1.6; margin: 0 0 12px; }
                .code-box { background: #f5f5f5; border: 2px solid #333; border-radius: 10px; padding: 24px 20px; text-align: center; margin: 24px 0; }
                .code { font-size: 42px; font-weight: 900; color: #111; letter-spacing: 10px; font-family: monospace; }
                .expiry { font-size: 12.5px; color: #777; margin-top: 10px; }
                .note { background: #f5f5f5; border-radius: 8px; padding: 12px 16px; font-size: 13px; color: #666; margin-top: 8px; }
                .footer { margin-top: 32px; font-size: 12px; color: #aaa; border-top: 1px solid #eee; padding-top: 16px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="logo">GMD South Phils</div>
                <h2>Password Reset Verification</h2>
                <p>Hello,</p>
                <p>We received a request to reset the password for your GMD account. Use the verification code below to proceed.</p>

                <div class="code-box">
                    <div class="code">' . $code . '</div>
                    <div class="expiry">Expires in 10 minutes</div>
                </div>

                <div class="note">
                    If you did not request a password reset, please ignore this email. Your account remains secure.
                </div>

                <div class="footer">
                    Thank you,<br>
                    <strong>GMD Construction Management Team</strong>
                </div>
            </div>
        </body>
        </html>';
    }
}
