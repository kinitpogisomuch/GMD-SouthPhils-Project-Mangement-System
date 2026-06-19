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
        $loginUrl = url('/login');
        $firstName = explode(' ', trim($name))[0];

        return '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Client Portal Account</title>
</head>
<body style="margin:0;padding:0;background:#f0f0f0;font-family:\'Segoe UI\',Arial,sans-serif;">

    <!-- Wrapper -->
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f0f0f0;padding:40px 16px;">
        <tr>
            <td align="center">
                <table width="100%" cellpadding="0" cellspacing="0" style="max-width:540px;">

                    <!-- Header banner -->
                    <tr>
                        <td style="background:linear-gradient(135deg,#1a1a1a 0%,#3a3a3a 100%);border-radius:16px 16px 0 0;padding:32px 36px 28px;">
                            <div style="font-size:11px;font-weight:700;color:rgba(255,255,255,0.5);text-transform:uppercase;letter-spacing:2px;margin-bottom:10px;">GMD South Phils</div>
                            <div style="font-size:22px;font-weight:900;color:#ffffff;line-height:1.2;margin-bottom:6px;">Your Client Portal<br>Account is Ready</div>
                            <div style="width:36px;height:3px;background:#e8900a;border-radius:2px;margin-top:14px;"></div>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td style="background:#ffffff;padding:32px 36px;">

                            <!-- Greeting -->
                            <p style="margin:0 0 8px;font-size:15px;color:#333;font-weight:600;">Hello, <span style="color:#1a1a1a;font-weight:800;">' . htmlspecialchars($firstName) . '</span> 👋</p>
                            <p style="margin:0 0 28px;font-size:14px;color:#666;line-height:1.7;">
                                Your GMD Client Portal account has been successfully created. Use the credentials below to access your project dashboard.
                            </p>

                            <!-- Credentials card -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="background:#f8f8f8;border:1px solid #e8e8e8;border-radius:12px;overflow:hidden;margin-bottom:24px;">
                                <tr>
                                    <td colspan="2" style="background:#1a1a1a;padding:12px 20px;">
                                        <span style="font-size:11px;font-weight:800;color:rgba(255,255,255,0.7);text-transform:uppercase;letter-spacing:1.5px;">Login Credentials</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:18px 20px 10px;border-bottom:1px solid #ececec;width:40%;">
                                        <div style="font-size:10px;font-weight:800;color:#999;text-transform:uppercase;letter-spacing:1px;margin-bottom:4px;">Username</div>
                                        <div style="font-size:20px;font-weight:900;color:#1a1a1a;letter-spacing:1.5px;font-family:\'Courier New\',monospace;">' . htmlspecialchars($username) . '</div>
                                    </td>
                                    <td style="padding:18px 20px 10px;border-bottom:1px solid #ececec;border-left:1px solid #ececec;">
                                        <div style="font-size:10px;font-weight:800;color:#999;text-transform:uppercase;letter-spacing:1px;margin-bottom:4px;">PIN / Password</div>
                                        <div style="font-size:20px;font-weight:900;color:#1a1a1a;letter-spacing:3px;font-family:\'Courier New\',monospace;">' . htmlspecialchars($pin) . '</div>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="2" style="padding:12px 20px;">
                                        <span style="font-size:12px;color:#999;">Keep these credentials safe and do not share them.</span>
                                    </td>
                                </tr>
                            </table>

                            <!-- Security notice -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="background:#fffbeb;border:1px solid #fcd34d;border-radius:10px;margin-bottom:28px;">
                                <tr>
                                    <td style="padding:14px 18px;">
                                        <table cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="vertical-align:top;padding-right:10px;font-size:16px;">🔒</td>
                                                <td style="font-size:13px;color:#92400e;line-height:1.6;">
                                                    <strong>Security reminder:</strong> We strongly recommend changing your PIN after your first login to keep your account secure.
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <!-- CTA button -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
                                <tr>
                                    <td align="center">
                                        <a href="' . $loginUrl . '" style="display:inline-block;background:#1a1a1a;color:#ffffff;font-size:14px;font-weight:700;text-decoration:none;padding:14px 36px;border-radius:8px;letter-spacing:0.3px;">
                                            Log In to Client Portal →
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:0;font-size:13px;color:#aaa;text-align:center;">
                                Or visit: <a href="' . $loginUrl . '" style="color:#e8900a;">' . $loginUrl . '</a>
                            </p>

                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background:#f8f8f8;border-top:1px solid #ebebeb;border-radius:0 0 16px 16px;padding:20px 36px;">
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td>
                                        <div style="font-size:13px;font-weight:700;color:#333;">GMD Construction Management Team</div>
                                        <div style="font-size:12px;color:#aaa;margin-top:3px;">GMD South Phils · Project Management System</div>
                                    </td>
                                    <td align="right">
                                        <div style="font-size:11px;color:#ccc;">This is an automated message.<br>Please do not reply.</div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>

</body>
</html>';
    }
}