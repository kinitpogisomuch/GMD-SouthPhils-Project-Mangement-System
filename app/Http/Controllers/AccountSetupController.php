<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Last step of client registration: Sign up → Verify email → Admin approves → Set Up Account → Log in.
 *
 * The admin's approval emails a one-time, time-limited link (only its SHA-256 hash is stored). The
 * page shows the client's auto-generated username (read-only) and lets them create a password.
 * Completing it hashes the password and makes the account Active — it does not log them in.
 */
class AccountSetupController extends Controller
{
    /** Setup links stay valid for 48 hours after the approval (or a resend) */
    public const LINK_HOURS = 48;

    public function show(string $token)
    {
        [$state, $client] = $this->resolve($token);

        if ($state !== 'ready') {
            return $this->resultPage($state, $client);
        }

        return view('auth.account_setup', ['client' => $client, 'token' => $token]);
    }

    public function complete(Request $request, string $token)
    {
        $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed',
                function ($_, $value, $fail) {
                    if (!preg_match('/[A-Z]/', $value)) $fail('Password must contain at least one uppercase letter.');
                    if (!preg_match('/[a-z]/', $value)) $fail('Password must contain at least one lowercase letter.');
                    if (!preg_match('/[0-9]/', $value)) $fail('Password must contain at least one number.');
                },
            ],
        ], [
            'password.required'  => 'Please create a password.',
            'password.min'       => 'Password must be at least 8 characters.',
            'password.confirmed' => 'Passwords do not match.',
        ]);

        // Locked row + re-checked state, so a double-submitted form can't complete it twice
        $result = DB::transaction(function () use ($token, $request) {
            [$state, $client] = $this->resolve($token, lockForUpdate: true);
            if ($state !== 'ready') {
                return [$state, $client];
            }

            $client->forceFill([
                'password'            => $request->password,   // hashed by the model's 'hashed' cast
                'status'              => 'Active',
                'first_login'         => false,
                'password_updated_at' => now(),
            ])->save();

            return ['completed', $client];
        });

        [$state, $client] = $result;
        if ($state !== 'completed') {
            return $this->resultPage($state, $client);
        }

        // Shown once on the "Your GMD Account Is Ready!" page (PRG, so a refresh can't resubmit)
        return redirect()->route('account.setup.done')->with('setup_done_username', $client->username);
    }

    public function done()
    {
        $username = session('setup_done_username');
        if (!$username) {
            return redirect()->route('login');
        }

        return view('auth.account_setup_result', ['state' => 'completed', 'username' => $username, 'email' => null]);
    }

    /** New setup link for an approved client whose link expired. Same reply whether or not the email matches. */
    public function resend(Request $request)
    {
        $request->validate(['email' => 'required|string|max:255']);
        $value = trim($request->input('email'));

        $client = Client::where('status', 'Approved')
            ->where(fn ($q) => $q->where('email', $value)->orWhere('username', $value))
            ->first();

        if ($client) {
            self::sendSetupEmail($client);
        }

        return view('auth.account_setup_result', ['state' => 'resent', 'username' => null, 'email' => null]);
    }

    /**
     * Where a link stands: ready / completed-before (already_done) / expired / not_allowed / invalid.
     * The token is kept after setup (single-purpose and inert once the account is Active) so a
     * repeat click can say "already set up" — it can never be used to change the password again.
     */
    private function resolve(string $token, bool $lockForUpdate = false): array
    {
        $query = Client::where('account_setup_token', hash('sha256', $token));
        if ($lockForUpdate) {
            $query->lockForUpdate();
        }
        $client = $query->first();

        if (!$client) {
            return ['invalid', null];
        }
        if ($client->status === 'Active') {
            return ['already_done', $client];
        }
        if ($client->status !== 'Approved' || !$client->hasVerifiedEmail()) {
            return ['not_allowed', $client];         // rejected, archived, or not approved
        }
        if (!$client->account_setup_sent_at || $client->account_setup_sent_at->lt(now()->subHours(self::LINK_HOURS))) {
            return ['expired', $client];
        }

        return ['ready', $client];
    }

    private function resultPage(string $state, ?Client $client)
    {
        return response()->view('auth.account_setup_result', [
            'state'    => $state,
            'username' => $state === 'already_done' ? $client?->username : null,
            'email'    => $state === 'expired' ? $client?->email : null,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Approval email with the Set Up Account button (used by the admin approval too)
    |--------------------------------------------------------------------------
    */

    /** Issues a fresh one-time setup link (any previous link stops working) and emails it. */
    public static function sendSetupEmail(Client $client): bool
    {
        $token = Str::random(64);

        $client->forceFill([
            'account_setup_token'   => hash('sha256', $token),
            'account_setup_sent_at' => now(),
        ])->save();

        if (!$client->email) {
            return false;
        }

        try {
            Mail::html(self::buildSetupEmailHtml($client->name, route('account.setup', ['token' => $token])), function ($message) use ($client) {
                $message->to($client->email, $client->name)
                        ->from(config('mail.from.address'), config('mail.from.name'))
                        ->replyTo(config('mail.from.address'), config('mail.from.name'))
                        ->subject('Your GMD Account Registration Has Been Approved');
            });
            Log::info('AccountSetup: setup email sent', ['email' => $client->email]);
            return true;
        } catch (\Exception $e) {
            Log::error('AccountSetup: setup email failed', ['email' => $client->email, 'error' => $e->getMessage()]);
            return false;
        }
    }

    private static function buildSetupEmailHtml(string $name, string $link): string
    {
        $hours = self::LINK_HOURS;

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
                .badge { display: inline-block; background: #dcfce7; color: #15803d; font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; border-radius: 999px; padding: 6px 14px; margin-bottom: 18px; }
                h2 { font-size: 18px; color: #1a1a2e; margin-bottom: 8px; }
                p { font-size: 14px; color: #444; line-height: 1.6; }
                .cta { display: inline-block; margin-top: 18px; background: #1a1a2e; color: #fff !important; text-decoration: none; font-weight: 700; font-size: 15px; padding: 14px 28px; border-radius: 8px; }
                .footer { margin-top: 32px; font-size: 12px; color: #aaa; border-top: 1px solid #eee; padding-top: 16px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="logo">GMD <span>South Phils</span></div>
                <div class="badge">&#10003; Registration Approved</div>
                <h2>Congratulations — your registration has been approved!</h2>
                <p>Hello <strong>' . htmlspecialchars($name) . '</strong>,</p>
                <p>Your GMD South Phils client portal registration has been reviewed and approved. One last step: set up your account by creating your password. Your username has been assigned automatically and will be shown on the setup page.</p>

                <a href="' . htmlspecialchars($link) . '" class="cta">Set Up Account</a>

                <p style="font-size:12px;color:#888;margin-top:22px;">This link can be used once and expires in ' . $hours . ' hours. If it expires, you can request a new one from the setup page.</p>

                <div class="footer">
                    Thank you,<br>
                    <strong>GMD Construction Management Team</strong>
                </div>
            </div>
        </body>
        </html>';
    }
}
