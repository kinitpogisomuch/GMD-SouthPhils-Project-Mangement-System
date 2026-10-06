<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Models\Client;
use App\Services\NotificationService;
use App\Http\Controllers\Concerns\BackdatesRecords;

class ClientSignupController extends Controller
{
    use BackdatesRecords;


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
            'street_address' => 'nullable|string|max:500',
            'username'       => 'required|string|max:50|alpha_dash|unique:clients,username',
            'signup_date'    => 'nullable|date',
            'signup_time'    => 'nullable|date_format:H:i',
            'password'       => ['required', 'string', 'min:8', 'confirmed',
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

        $client = new Client([
            'name'           => $request->full_name,
            'email'          => $request->email,
            'contact'        => $request->contact_number,
            'address'        => $fullAddress,
            'region'         => $request->region,
            'province'       => $request->province,
            'city'           => $request->city,
            'barangay'       => $request->barangay,
            'street_address' => $request->street_address,
            'status'         => 'Pending',          // Account: Pending Approval
            'username'       => $request->username,
            'password'       => $request->password,
            'first_login'    => false,
            'email_verified_at' => null,            // Email: Unverified
        ]);
        $this->applyBackdate($client, $request->signup_date, $request->signup_time);
        $client->save();

        // The admin only hears about the sign-up once the email is verified (see verify()).
        $sent = $this->sendVerificationEmail($client);

        session(['verify_email_address' => $client->email]);

        return redirect()->route('signup.verify_notice')->with('verify_sent', $sent);
    }

    /*
    |--------------------------------------------------------------------------
    | Email verification — separate from admin approval
    |--------------------------------------------------------------------------
    | Sign Up → Verify Email → Wait for Admin Approval → Admin Approves → Client can log in
    */

    /** Links stay valid for 48 hours */
    private const VERIFY_LINK_HOURS = 48;

    /** "Check your email" page shown right after signing up (and after a resend) */
    public function verifyNotice()
    {
        return view('auth.verify_email', [
            'state' => 'sent',
            'email' => session('verify_email_address'),
            'sent'  => session('verify_sent', true),
        ]);
    }

    /**
     * The link in the email. Marks the email Verified and tells the admin a client is waiting
     * for approval. It does NOT approve the account and does NOT log the client in.
     */
    public function verify(string $token)
    {
        $client = Client::where('email_verification_token', hash('sha256', $token))->first();

        if (!$client) {
            return view('auth.verify_email', ['state' => 'invalid', 'email' => null, 'sent' => false]);
        }

        if ($client->email_verification_sent_at && $client->email_verification_sent_at->lt(now()->subHours(self::VERIFY_LINK_HOURS))) {
            return view('auth.verify_email', ['state' => 'expired', 'email' => $client->email, 'sent' => false]);
        }

        $client->forceFill([
            'email_verified_at'        => now(),
            'email_verification_token' => null,
        ])->save();

        // Now that the email is real, the sign-up enters the admin's approval queue
        if ($client->status === 'Pending') {
            NotificationService::clientSignupPending($client);
        }

        return view('auth.verify_email', ['state' => 'verified', 'email' => $client->email, 'sent' => false]);
    }

    /** Send a fresh link (the old one stops working). Same reply whether or not the email is on file. */
    public function resend(Request $request)
    {
        $request->validate(['email' => 'required|string|max:255']);
        $value = trim($request->input('email'));

        $client = Client::whereNull('email_verified_at')
            ->where(function ($q) use ($value) {
                $q->where('email', $value)->orWhere('username', $value);
            })
            ->first();

        $sent = true;
        if ($client) {
            $sent = $this->sendVerificationEmail($client);
            $value = $client->email;
        }

        session(['verify_email_address' => $value]);

        return redirect()->route('signup.verify_notice')
            ->with('verify_sent', $sent)
            ->with('verify_resent', true);
    }

    /** New one-time token (only its hash is stored) + the email with the Verify Email button */
    private function sendVerificationEmail(Client $client): bool
    {
        $token = Str::random(64);

        $client->forceFill([
            'email_verification_token'   => hash('sha256', $token),
            'email_verification_sent_at' => now(),
        ])->save();

        $link = route('signup.verify', ['token' => $token]);

        try {
            Mail::html($this->buildVerificationEmailHtml($client->name, $link), function ($message) use ($client) {
                $message->to($client->email, $client->name)
                        ->from(config('mail.from.address'), config('mail.from.name'))
                        ->replyTo(config('mail.from.address'), config('mail.from.name'))
                        ->subject('Verify your email — GMD South Phils Client Portal');
            });
            return true;
        } catch (\Exception $e) {
            Log::error('ClientSignup: verification email failed', ['email' => $client->email, 'error' => $e->getMessage()]);
            return false;
        }
    }

    private function buildVerificationEmailHtml(string $name, string $link): string
    {
        $hours = self::VERIFY_LINK_HOURS;

        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 20px; }
                .container { background: #fff; max-width: 520px; margin: 0 auto; border-radius: 12px; padding: 36px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); }
                .logo { font-size: 20px; font-weight: 900; color: #1a1a2e; margin-bottom: 24px; }
                .logo span { color: #e8900a; }
                .badge { display: inline-block; background: #EAF0FF; color: #2A4EAA; font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; border-radius: 999px; padding: 6px 14px; margin-bottom: 18px; }
                h2 { font-size: 18px; color: #1a1a2e; margin-bottom: 8px; }
                p { font-size: 14px; color: #444; line-height: 1.6; }
                .cta { display: inline-block; margin-top: 16px; background: #1a1a2e; color: #fff !important; text-decoration: none; font-weight: 700; font-size: 14px; padding: 12px 24px; border-radius: 8px; }
                .link { word-break: break-all; font-size: 12px; color: #888; }
                .footer { margin-top: 32px; font-size: 12px; color: #aaa; border-top: 1px solid #eee; padding-top: 16px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="logo">GMD <span>South Phils</span></div>
                <div class="badge">Verify your email</div>
                <h2>Confirm your email address</h2>
                <p>Hello <strong>' . htmlspecialchars($name) . '</strong>,</p>
                <p>Thanks for signing up for the GMD South Phils client portal. Please confirm this is your email address by clicking the button below.</p>

                <a href="' . htmlspecialchars($link) . '" class="cta">Verify Email</a>

                <p style="margin-top:22px;">After you verify, our team will review your account. You\'ll get another email once it\'s approved and you can log in.</p>
                <p class="link">If the button doesn\'t work, copy this link into your browser:<br>' . htmlspecialchars($link) . '</p>
                <p style="font-size:12px;color:#888;">This link expires in ' . $hours . ' hours. If you didn\'t sign up, you can ignore this email.</p>

                <div class="footer">
                    Thank you,<br>
                    <strong>GMD Construction Management Team</strong>
                </div>
            </div>
        </body>
        </html>';
    }
}
