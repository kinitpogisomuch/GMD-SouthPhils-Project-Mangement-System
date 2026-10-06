<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
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

    /** Common misspellings of popular email domains → the domain the client most likely meant */
    private const DOMAIN_TYPOS = [
        'gmial.com' => 'gmail.com', 'gmai.com' => 'gmail.com', 'gmal.com' => 'gmail.com', 'gamil.com' => 'gmail.com',
        'gmail.co' => 'gmail.com', 'gmail.con' => 'gmail.com', 'gmail.cm' => 'gmail.com', 'gnail.com' => 'gmail.com',
        'yaho.com' => 'yahoo.com', 'yahooo.com' => 'yahoo.com', 'yahoo.con' => 'yahoo.com', 'yhoo.com' => 'yahoo.com',
        'hotmial.com' => 'hotmail.com', 'hotmai.com' => 'hotmail.com', 'hotmail.con' => 'hotmail.com',
        'outlok.com' => 'outlook.com', 'outloo.com' => 'outlook.com', 'outlook.con' => 'outlook.com',
        'iclod.com' => 'icloud.com', 'icloud.con' => 'icloud.com',
    ];

    /**
     * Why an email can't be used to sign up, or null when it's fine. Checks the format, common
     * domain typos, and that the domain really exists and can receive mail (MX / A record).
     * A mailbox itself can't be confirmed without emailing it — mail servers don't reveal that.
     */
    private function emailProblem(string $email): ?string
    {
        $email = trim($email);

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return 'Please enter a valid email address (e.g. name@gmail.com).';
        }

        $domain = strtolower(substr(strrchr($email, '@'), 1));

        if (isset(self::DOMAIN_TYPOS[$domain])) {
            $local = substr($email, 0, strrpos($email, '@'));
            return "This email doesn't exist. Did you mean {$local}@" . self::DOMAIN_TYPOS[$domain] . '?';
        }

        $domainExists = checkdnsrr($domain, 'MX') || checkdnsrr($domain, 'A');
        if (!$domainExists) {
            return "This email doesn't exist — \"@{$domain}\" is not a real email domain. Please check it and try again.";
        }

        if (Client::where('email', $email)->exists()) {
            return 'An account with this email address already exists.';
        }

        return null;
    }

    /** Live check from the sign-up form (when the client leaves the email box) */
    public function checkEmail(Request $request): \Illuminate\Http\JsonResponse
    {
        $problem = $this->emailProblem((string) $request->query('email', ''));

        return response()->json(['ok' => $problem === null, 'message' => $problem]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'full_name'      => 'required|string|max:255',
            'email'          => ['required', 'string', 'max:255',
                function ($_, $value, $fail) {
                    if ($problem = $this->emailProblem((string) $value)) {
                        $fail($problem);
                    }
                },
            ],
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
            'status'         => 'Pending',
            'username'       => $request->username,
            'password'       => $request->password,
            'first_login'    => false,
        ]);
        $this->applyBackdate($client, $request->signup_date, $request->signup_time);
        $client->save();

        NotificationService::clientSignupPending($client);

        return redirect()->route('login')
            ->with('success', 'Your account has been created. Please wait for GMD South Phils to review and approve it — we\'ll send you an email as soon as your account is approved so you can log in.');
    }
}
