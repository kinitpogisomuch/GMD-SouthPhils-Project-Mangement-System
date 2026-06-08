<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\Client;

class ClientAccountController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Get next available GMD-XXXX username (for live preview in form)
    |--------------------------------------------------------------------------
    */
    public function nextUsername(): \Illuminate\Http\JsonResponse
    {
        $row = \DB::selectOne(
            "SELECT COALESCE(MAX(CAST(SUBSTRING(username FROM 6) AS INTEGER)), 0) AS max_num
             FROM clients WHERE username ~ '^CGMD-[0-9]+$'"
        );

        $nextNum  = (int) ($row->max_num ?? 0) + 1;
        $username = 'CGMD-' . str_pad($nextNum, 4, '0', STR_PAD_LEFT);

        return response()->json(['username' => $username]);
    }

    /*
    |--------------------------------------------------------------------------
    | Generate unique CGMD-XXXX username
    |--------------------------------------------------------------------------
    */
    private function generateUsername(): string
    {
        $row = \DB::selectOne(
            "SELECT COALESCE(MAX(CAST(SUBSTRING(username FROM 6) AS INTEGER)), 0) AS max_num
             FROM clients WHERE username ~ '^CGMD-[0-9]+$'"
        );

        $nextNum = (int) ($row->max_num ?? 0) + 1;

        do {
            $username = 'CGMD-' . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
            if (!Client::where('username', $username)->exists()) {
                return $username;
            }
            $nextNum++;
        } while (true);
    }

    /*
    |--------------------------------------------------------------------------
    | Generate random 6-digit numeric PIN
    |--------------------------------------------------------------------------
    */
    private function generatePin(): string
    {
        return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /*
    |--------------------------------------------------------------------------
    | Store new client account
    |--------------------------------------------------------------------------
    */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'full_name'      => 'required|string|max:255',
            'email'          => 'required|email|unique:clients,email',
            'contact_number' => 'required|string|max:20',
            'address'        => 'nullable|string|max:500',
        ], [
            'email.unique' => 'An account with this email address already exists.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator, 'client_account')
                ->withInput()
                ->with('active_tab', 'users');
        }

        $username = $this->generateUsername();
        $pin      = $this->generatePin();

        try {
            $client = Client::create([
                'name'        => $request->full_name,
                'email'       => $request->email,
                'contact'     => $request->contact_number,
                'address'     => $request->address,
                'status'      => 'Active',
                'username'    => $username,
                'password'    => $pin,
                'first_login' => true,
            ]);
        } catch (\Exception $e) {
            \Log::error('ClientAccount: client creation failed', ['error' => $e->getMessage()]);
            return redirect()->back()
                ->withInput()
                ->with('active_tab', 'users')
                ->with('account_error', 'Account creation failed: ' . $e->getMessage());
        }

        $emailSent = false;
        try {
            Mail::html(
                $this->buildEmailHtml($request->full_name, $username, $pin),
                function ($message) use ($request) {
                    $message->to($request->email, $request->full_name)
                            ->subject('Your GMD Client Portal Account Credentials');
                }
            );
            $emailSent = true;
        } catch (\Exception $e) {
            \Log::error('ClientAccount: email failed', ['error' => $e->getMessage()]);
        }

        if ($emailSent) {
            $client->update(['credentials_sent_at' => now()]);
        }

        return redirect()->route('admin.settings')
            ->with([
                'active_tab'          => 'users',
                'new_client_username' => $username,
                'new_client_pin'      => $pin,
                'new_client_name'     => $request->full_name,
                'new_client_email'    => $request->email,
                'email_sent'          => $emailSent,
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Build credentials email HTML
    |--------------------------------------------------------------------------
    */
    private function buildEmailHtml(string $name, string $username, string $pin): string
    {
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
                h2 { font-size: 18px; color: #1a1a2e; margin-bottom: 8px; }
                p { font-size: 14px; color: #444; line-height: 1.6; }
                .credentials-box { background: #f8f9ff; border: 1.5px solid #dde1f5; border-radius: 10px; padding: 20px 24px; margin: 24px 0; }
                .cred-row { display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid #eee; }
                .cred-row:last-child { border-bottom: none; }
                .cred-label { font-size: 12px; font-weight: 700; color: #888; text-transform: uppercase; }
                .cred-value { font-size: 18px; font-weight: 900; color: #1a1a2e; letter-spacing: 2px; }
                .note { background: #fff7ed; border: 1px solid #fed7aa; border-radius: 8px; padding: 12px 16px; font-size: 13px; color: #92400e; margin-top: 20px; }
                .footer { margin-top: 32px; font-size: 12px; color: #aaa; border-top: 1px solid #eee; padding-top: 16px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="logo">GMD <span>South Phils</span></div>
                <h2>Your Client Portal Account is Ready</h2>
                <p>Hello <strong>' . htmlspecialchars($name) . '</strong>,</p>
                <p>Your client portal account has been successfully created. Use the credentials below to log in.</p>

                <div class="credentials-box">
                    <div class="cred-row">
                        <span class="cred-label">Username</span>
                        <span class="cred-value">' . htmlspecialchars($username) . '</span>
                    </div>
                    <div class="cred-row">
                        <span class="cred-label">PIN / Password</span>
                        <span class="cred-value">' . $pin . '</span>
                    </div>
                </div>

                <div class="note">
                    ⚠ For security purposes, we recommend changing your PIN after your first login.
                </div>

                <p style="margin-top:20px;">You may now log in to the Client Portal using the credentials above.</p>

                <div class="footer">
                    Thank you,<br>
                    <strong>GMD Construction Management Team</strong>
                </div>
            </div>
        </body>
        </html>';
    }
}