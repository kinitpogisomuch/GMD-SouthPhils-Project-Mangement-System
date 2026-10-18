<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Models\Client;
use App\Services\NotificationService;

class ClientSignupController extends Controller
{
    public function show()
    {
        if (session('role') === 'admin')    return redirect()->route('admin.dashboard');
        if (session('role') === 'client')   return redirect()->route('client.dashboard');
        if (session('role') === 'employee') return redirect()->route('employee.dashboard');

        return view('auth.signup', ['quoteTank' => \App\Support\TankCatalog::find(session('quote_intent'))]);
    }

    /**
     * Registration request. Collects the client's details only — the username is generated here
     * (CGMD- + 4 digits) and the password is set later, on the Set Up Account page, once the admin
     * has approved the request. Starts as Pending Email Verification; never logs anyone in.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'full_name'      => 'required|string|max:255',
            'email'          => 'required|email|unique:clients,email',
            // digits only: PH mobile number, 09XXXXXXXXX (11 digits)
            // one account per mobile number (stored in clients.contact) — compared digits-only
            'contact_number' => ['required', 'regex:/^09\d{9}$/',
                function ($_, $value, $fail) {
                    if (self::contactNumberTaken((string) $value)) {
                        $fail('This contact number is already registered to another account. Please use a different number.');
                    }
                },
            ],
            'region'         => 'required|string|max:255',
            'province'       => 'required|string|max:255',
            'city'           => 'required|string|max:255',
            'barangay'       => 'required|string|max:255',
            'street_address' => 'nullable|string|max:500',
            'terms'          => 'accepted',
        ], [
            'terms.accepted'  => 'Please agree to the Terms and Conditions to create an account.',
            'contact_number.regex' => 'Enter a valid mobile number — 11 digits starting with 09 (09XXXXXXXXX).',
            'email.unique'    => 'An account with this email address already exists.',
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

        try {
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
                'status'         => 'Pending',                                    // Pending Email Verification
                'username'       => Client::generateRegistrationUsername(),       // CGMD- + 4 digits, fixed from now on
                'password'       => null,                                         // set on the Set Up Account page
                'first_login'    => false,
                'email_verified_at' => null,
                // the tank card they clicked on the landing page — their first login opens that quotation form
                'pending_quote_tank' => \App\Support\TankCatalog::find(session('quote_intent')) ? session('quote_intent') : null,
            ]);
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            // a double-submitted form (or a race on the same email) — never create a duplicate request
            return redirect()->back()->withInput()
                ->withErrors(['email' => 'An account with this email address already exists.']);
        }

        // The admin only hears about the sign-up once the email is verified (see verify()).
        $sent = $this->sendVerificationEmail($client);

        session(['verify_email_address' => $client->email]);

        return redirect()->route('signup.verify_notice')->with('verify_sent', $sent);
    }

    /*
    |--------------------------------------------------------------------------
    | Email verification — separate from admin approval
    |--------------------------------------------------------------------------
    | Sign Up → Verify Email → Wait for Admin Approval → Admin Approves (setup email) → Set Up Account → Log in
    */

    /** Links stay valid for 10 minutes — after that, a new one can be requested */
    private const VERIFY_LINK_MINUTES = 10;

    /** "Check your email" page shown right after signing up (and after a resend) */
    public function verifyNotice()
    {
        // Once the email has been verified (link opened in another tab or on another device), this same
        // page shows its "verified" status — it updates in place while open, and on refresh
        $verifiedClient = $this->sessionClientIfVerified();

        return view('auth.verify_email', [
            'state' => $verifiedClient ? 'verified' : 'sent',
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

        // The result always lands on its own page (auth.email_verified) — never the dashboard or
        // login — and verifying never logs the client in or approves the account.
        if (!$client) {
            return $this->verificationResult('invalid');
        }

        // Clicking the link again after verifying: say so, instead of "invalid link"
        if ($client->email_verified_at) {
            return $this->verificationResult('already_verified', $client);
        }

        if ($client->email_verification_sent_at && $client->email_verification_sent_at->lt(now()->subMinutes(self::VERIFY_LINK_MINUTES))) {
            return $this->verificationResult('expired', $client);
        }

        // Only the email is marked verified — status stays 'Pending' until an admin approves it.
        // The token is kept (it's single-purpose and now inert) so a repeat click is recognised as
        // "already verified"; resending is only possible for unverified emails.
        $client->forceFill(['email_verified_at' => now()])->save();

        // Now that the email is real, the sign-up enters the admin's approval queue
        if ($client->status === 'Pending') {
            NotificationService::clientSignupPending($client);
        }

        return $this->verificationResult('verified', $client);
    }

    /**
     * Live check from the sign-up form: is this email / mobile number already registered?
     * Same uniqueness rules as store() — that remains the real check on submit.
     */
    public function checkAvailability(Request $request)
    {
        $field = $request->query('field');
        $value = trim((string) $request->query('value', ''));

        if ($value === '' || !in_array($field, ['email', 'contact_number'], true)) {
            return response()->json(['taken' => false]);
        }

        $taken = $field === 'email'
            ? Client::where('email', $value)->exists()
            : self::contactNumberTaken($value);

        return response()->json(['taken' => $taken]);
    }

    /**
     * Whether a mobile number already belongs to a client. Compares digits only, since older
     * records were saved with spaces (e.g. "0956 802 3131" is the same number as "09568023131").
     */
    private static function contactNumberTaken(string $number): bool
    {
        $digits = preg_replace('/\D/', '', $number);

        return $digits !== '' && Client::whereRaw("regexp_replace(coalesce(contact, ''), '\\D', '', 'g') = ?", [$digits])->exists();
    }

    /**
     * Polled by the "check your email" page: has the email this browser signed up with been
     * verified yet? Scoped to the session's own email, so it can't be used to probe other addresses.
     */
    public function verifyStatus()
    {
        return response()->json(['verified' => (bool) $this->sessionClientIfVerified()]);
    }

    /** The client this session signed up (or resent) with, if their email is verified */
    private function sessionClientIfVerified(): ?Client
    {
        $email = session('verify_email_address');

        return $email ? Client::where('email', $email)->whereNotNull('email_verified_at')->first() : null;
    }

    /** Dedicated verification result page: verified / already_verified / expired / invalid */
    private function verificationResult(string $state, ?Client $client = null)
    {
        return response()->view('auth.email_verified', [
            'state'    => $state,
            'email'    => $client?->email,
            // approved by the admin (Approved = still has to set up the account; Active = can log in)
            'approved' => $client && in_array($client->status, ['Approved', 'Active'], true),
            'active'   => $client && $client->status === 'Active',
        ]);
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
        $minutes = self::VERIFY_LINK_MINUTES;

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

                <p style="margin-top:22px;">After you verify, our team will review your registration. Once it\'s approved, we\'ll email you a link to set up your account password.</p>
                <p style="font-size:12px;color:#888;">This link expires in ' . $minutes . ' minutes. If you didn\'t sign up, you can ignore this email.</p>

                <div class="footer">
                    Thank you,<br>
                    <strong>GMD Construction Management Team</strong>
                </div>
            </div>
        </body>
        </html>';
    }
}
