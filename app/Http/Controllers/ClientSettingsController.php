<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use App\Models\Client;

class ClientSettingsController extends Controller
{
    public function index()
    {
        $clients = Client::orderBy('name')->get();
        return view('admin.settings', compact('clients'));
    }

    public function store(Request $request)
    {
        $hasEmail = $request->filled('email');

        $rules = [
            'full_name' => 'required|string|max:255',
            'contact'   => 'required|string|max:20',
            'email'     => $hasEmail
                               ? 'required|email|unique:clients,email'
                               : 'nullable|email',
        ];

        $validator = Validator::make($request->all(), $rules, [
            'email.unique' => 'An account with this email address already exists.',
        ]);

        if ($validator->fails()) {
            return redirect()->route('admin.clients')
                ->withErrors($validator, 'client')
                ->withInput();
        }

        $fullName = trim($request->full_name);

        if ($hasEmail) {
            $username = $this->generateUsername();
            $pin      = $this->generatePin();

            try {
                $client = Client::updateOrCreate(
                    ['email' => $request->email],
                    [
                        'name'       => $fullName,
                        'first_name' => null,
                        'last_name'  => null,
                        'contact'    => $request->contact,
                        'email'      => $request->email,
                        'username'   => $username,
                        'password'   => bcrypt($pin),
                        'first_login' => true,
                        'status'     => 'Active',
                    ]
                );
            } catch (\Exception $e) {
                Log::error('ClientSettings: client creation failed', ['error' => $e->getMessage()]);
                return redirect()->route('admin.clients')
                    ->with('success', 'Client added successfully!');
            }

            $emailSent = false;
            try {
                Mail::html(
                    $this->buildEmailHtml($fullName, $username, $pin),
                    function ($message) use ($request, $fullName) {
                        $message->to($request->email, $fullName)
                                ->subject('Your GMD Client Portal Account Credentials');
                    }
                );
                $emailSent = true;
                Log::info('ClientSettings: credentials email sent', ['email' => $request->email]);
            } catch (\Exception $e) {
                Log::error('ClientSettings: email failed', ['error' => $e->getMessage()]);
            }

            if ($emailSent) {
                $client->update(['credentials_sent_at' => now()]);
            }

            return redirect()->route('admin.clients')
                ->with([
                    'new_client_username' => $username,
                    'new_client_pin'      => $pin,
                    'new_client_name'     => $fullName,
                    'new_client_email'    => $request->email,
                    'email_sent'          => $emailSent,
                ]);
        }

        // No email — create a contact-only record (no login)
        Client::create([
            'name'       => $fullName,
            'first_name' => null,
            'last_name'  => null,
            'contact'    => $request->contact,
        ]);

        return redirect()->route('admin.clients')
            ->with('success', 'Client added successfully!');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'first_name'     => 'required|string|max:255',
            'last_name'      => 'required|string|max:255',
            'contact'        => 'required|string',
            'email'          => 'nullable|email',
            'province'       => 'nullable|string|max:255',
            'city'           => 'nullable|string|max:255',
            'region'         => 'nullable|string|max:255',
            'street_address' => 'nullable|string|max:500',
        ]);

        $combinedName = trim($request->last_name . ', ' . $request->first_name);

        $fullAddress = null;
        if ($request->filled('street_address') && $request->filled('city') && $request->filled('province')) {
            $fullAddress = implode(', ', array_filter([
                $request->street_address,
                $request->barangay,
                $request->city,
                $request->province,
                $request->region,
            ]));
        }

        Client::findOrFail($id)->update([
            'name'           => $combinedName,
            'first_name'     => $request->first_name,
            'last_name'      => $request->last_name,
            'address'        => $fullAddress,
            'region'         => $request->region,
            'province'       => $request->province,
            'city'           => $request->city,
            'barangay'       => $request->barangay,
            'street_address' => $request->street_address,
            'contact'        => $request->contact,
            'email'          => $request->email,
        ]);

        return redirect()->route('admin.clients')
            ->with('success', 'Client updated successfully!');
    }

    public function destroy($id)
    {
        Client::findOrFail($id)->delete();

        return redirect()->route('admin.clients')
            ->with('success', 'Client deleted successfully!');
    }

    public function archive($id)
    {
        $client    = Client::findOrFail($id);
        $restore   = ($client->status === 'Inactive');
        $newStatus = $restore ? 'Active' : 'Inactive';

        $client->update(['status' => $newStatus]);

        return redirect()->route('admin.clients')
            ->with('success', $restore ? 'Client restored successfully.' : 'Client archived successfully.');
    }

    public function list()
    {
        $clients = Client::orderBy('last_name')->orderBy('first_name')
                         ->get(['id', 'name', 'first_name', 'last_name', 'address', 'province', 'city', 'contact', 'email'])
                         ->map(function ($c) {
                             return [
                                 'id'      => $c->id,
                                 'name'    => $c->full_name,
                                 'address' => ($c->province && $c->city)
                                              ? $c->province . ', ' . $c->city
                                              : ($c->address ?? ''),
                                 'contact' => $c->contact,
                                 'email'   => $c->email,
                             ];
                         });

        return response()->json($clients);
    }

    // -----------------------------------------------------------------------
    // Shared credential helpers
    // -----------------------------------------------------------------------

    private function generateUsername(): string
    {
        $row = DB::selectOne(
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

    private function generatePin(): string
    {
        return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    }

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
                    &#9888; For security purposes, we recommend changing your PIN after your first login.
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
