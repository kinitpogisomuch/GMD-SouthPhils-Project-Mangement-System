<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function index()
    {
        if (session('role') === 'admin')    return redirect()->route('admin.dashboard');
        if (session('role') === 'client')   return redirect()->route('client.dashboard');
        if (session('role') === 'employee') return redirect()->route('employee.dashboard');

        $portfolioItems = \App\Models\PortfolioItem::active()->orderBy('sort_order')->get();
        $reviews = \App\Models\Review::active()->with('project')->latest()->take(6)->get();
        $allReviews = \App\Models\Review::active()->with('project')->latest()->get();
        $contactInfo = \App\Models\SiteSetting::instance();
        // "Our Work" tank cards — each one starts a quotation request for that tank
        $tankCards = \App\Support\TankCatalog::cards($portfolioItems);

        return view('landing', compact('portfolioItems', 'reviews', 'allReviews', 'contactInfo', 'tankCards'));
    }

    /**
     * A tank card on the landing page was clicked. A logged-in client goes straight to that tank's
     * quotation form; a visitor is sent to log in or sign up with the choice remembered, so after
     * authentication (and, for new accounts, approval and setup) they land on the same form.
     */
    /** A tank card's full details (sizes, what's included, what the form asks) before requesting a quotation */
    public function tankDetails(string $tank)
    {
        // a type that is now part of a combined card (e.g. re-piping → Fuel Pipeline) opens that card
        if ($merged = \App\Support\TankCatalog::mergedInto($tank)) {
            return redirect()->route('tanks.show', $merged, 301);
        }
        $card = \App\Support\TankCatalog::card($tank);
        abort_unless($card, 404);

        $others = array_values(array_filter(\App\Support\TankCatalog::cards(), fn ($c) => $c['slug'] !== $tank));

        return view('tanks.show', ['card' => $card, 'others' => array_slice($others, 0, 3)]);
    }

    public function quoteIntent(Request $request, string $tank)
    {
        $tank = \App\Support\TankCatalog::mergedInto($tank) ?? $tank;
        abort_unless(in_array($tank, \App\Support\TankCatalog::requestableSlugs(), true), 404);

        // a standard size picked on the details page travels with the tank (anything else is ignored)
        $capacity = \App\Support\TankCatalog::standardCapacity($tank, $request->query('capacity'));
        // …and so does the option picked on a combined card (e.g. Fuel Pipeline → Re-piping)
        $choice   = \App\Support\TankCatalog::validChoice($tank, $request->query('choice'));

        if (session('role') === 'client') {
            return redirect()->route('client.quotation.create', array_filter(['tank' => $tank, 'capacity' => $capacity, 'choice' => $choice]));
        }
        if (session('role')) {
            return redirect()->route('home');
        }

        session(['quote_intent' => $tank, 'quote_capacity' => $capacity, 'quote_choice' => $choice]);

        return $request->query('then') === 'signup'
            ? redirect()->route('signup')
            : redirect()->route('login');
    }

    public function showLogin()
    {
        if (session('role') === 'admin') {
            return redirect()->route('admin.dashboard');
        }
        if (session('role') === 'client') {
            return redirect()->route('client.dashboard');
        }
        if (session('role') === 'employee') {
            return redirect()->route('employee.dashboard');
        }

        // a tank picked on the landing page stays in session('quote_intent'); login() sends them on to that quotation
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $username = trim($request->input('email'));
        $password = trim($request->input('password'));

        // 1. Admin — check users table
        $user = DB::table('users')
            ->where(function ($q) use ($username) {
                $q->where('username', $username)->orWhere('email', $username);
            })
            ->first();

        if ($user && Hash::check($password, $user->password)) {
            if ($user->status !== 'Active') {
                return redirect()->route('login')
                    ->with('error', 'Your account is inactive. Please contact the administrator.')
                    ->withInput(['email' => $username]);
            }
            $request->session()->flush();
            session([
                'user_id'       => $user->id,
                'full_name'     => $user->full_name,
                'name'          => $user->full_name,
                'role'          => $user->role,
                'email'         => $user->email,
                'first_login'   => (bool) $user->first_login,
                'profile_photo' => $user->profile_photo,
            ]);
            if ($user->first_login) return redirect()->route('setup.credentials');
            return redirect()->route('admin.dashboard');
        }

        // 2. Employee — check employees table
        $employee = DB::table('employees')
            ->whereNotNull('username')
            ->where(function ($q) use ($username) {
                $q->where('username', $username)->orWhere('email', $username);
            })
            ->first();

        if ($employee && $employee->password && Hash::check($password, $employee->password)) {
            if ($employee->status !== 'Active') {
                return redirect()->route('login')
                    ->with('error', 'Your account is inactive. Please contact the administrator.')
                    ->withInput(['email' => $username]);
            }
            $request->session()->flush();
            session([
                'user_id'       => $employee->id,
                'full_name'     => trim($employee->last_name . ', ' . $employee->first_name),
                'name'          => trim($employee->first_name . ' ' . $employee->last_name),
                'role'          => 'employee',
                'email'         => $employee->email,
                'first_login'   => (bool) $employee->first_login,
                'profile_photo' => $employee->profile_photo,
            ]);
            if ($employee->first_login) return redirect()->route('setup.credentials');
            return redirect()->route('employee.dashboard');
        }

        // 3. Client — check clients table
        $client = DB::table('clients')
            ->whereNotNull('username')
            ->where(function ($q) use ($username) {
                $q->where('username', $username)->orWhere('email', $username);
            })
            ->first();

        // Self-registered clients have no password until they finish account setup, so tell them
        // where their registration stands instead of "invalid credentials". (No rejection reason
        // here — without a password there's nothing proving who is asking.)
        if ($client && !$client->password) {
            $status = match (true) {
                $client->status === 'Pending' && $client->email_verified_at === null
                    => ['error', 'Please verify your email address first. We sent a verification link to ' . $client->email . '.'],
                $client->status === 'Pending'
                    => ['info', 'Your email has been verified. Your registration is waiting for administrator approval.'],
                $client->status === 'Approved'
                    => ['info', 'Your registration has been approved. Please set up your account using the "Set Up Account" link we emailed to ' . $client->email . ' before logging in.'],
                $client->status === 'Rejected'
                    => ['error', 'Your registration was not approved by GMD South Phils. Please contact us if you have questions.'],
                default => null,
            };
            if ($status) {
                $redirect = redirect()->route('login')->with($status[0], $status[1])->withInput(['email' => $username]);
                return $client->status === 'Pending' && $client->email_verified_at === null
                    ? $redirect->with('resend_verification_email', $client->email)
                    : $redirect;
            }
        }

        if ($client && $client->password && Hash::check($password, $client->password)) {
            // Email verification comes first, and is separate from admin approval
            if ($client->status === 'Pending' && $client->email_verified_at === null) {
                return redirect()->route('login')
                    ->with('error', 'Please verify your email address first. We sent a verification link to ' . $client->email . '.')
                    ->with('resend_verification_email', $client->email)
                    ->withInput(['email' => $username]);
            }
            if ($client->status === 'Pending') {
                return redirect()->route('login')
                    ->with('info', 'Your email has been verified. Your account is currently waiting for admin approval.')
                    ->withInput(['email' => $username]);
            }
            if ($client->status === 'Rejected') {
                $reason = trim((string) ($client->rejection_reason ?? ''));
                return redirect()->route('login')
                    ->with('error', 'Your account application was rejected by GMD South Phils.' .
                        ($reason !== '' ? ' Reason: ' . $reason : '') .
                        ' Please contact us if you have questions.')
                    ->withInput(['email' => $username]);
            }
            if ($client->status !== 'Active') {
                return redirect()->route('login')
                    ->with('error', 'Your account is inactive. Please contact the administrator.')
                    ->withInput(['email' => $username]);
            }
            // A tank picked on the landing page — in this browser, or before signing up — survives the login
            $quoteIntent = session('quote_intent') ?: $client->pending_quote_tank;
            $quoteIntent = \App\Support\TankCatalog::find($quoteIntent) ? $quoteIntent : null;
            $quoteCapacity = $quoteIntent ? \App\Support\TankCatalog::standardCapacity($quoteIntent, session('quote_capacity')) : null;
            $quoteChoice   = $quoteIntent ? \App\Support\TankCatalog::validChoice($quoteIntent, session('quote_choice')) : null;
            $request->session()->flush();
            session([
                'user_id'       => $client->id,
                'full_name'     => $client->name,
                'name'          => $client->name,
                'role'          => 'client',
                'email'         => $client->email,
                'first_login'   => (bool) $client->first_login,
                'profile_photo' => $client->profile_photo,
            ]);
            if ($quoteIntent) {
                // used (and forgotten) by the quotation page
                session(['quote_intent' => $quoteIntent, 'quote_capacity' => $quoteCapacity, 'quote_choice' => $quoteChoice]);
            }
            if ($client->first_login) return redirect()->route('setup.credentials');
            if ($quoteIntent) {
                return redirect()->route('client.quotation.create', array_filter(['tank' => $quoteIntent, 'capacity' => $quoteCapacity, 'choice' => $quoteChoice]))
                    ->with('success', 'Welcome back! Tell us about the ' . \App\Support\TankCatalog::find($quoteIntent)['type'] . ' you need.');
            }
            return redirect()->route('client.dashboard');
        }

        return redirect()->route('login')
            ->with('error', 'Invalid credentials. Please check your username and password.')
            ->withInput(['email' => $username]);
    }

    public function logout(Request $request)
    {
        $request->session()->flush();
        $request->session()->regenerate();
        return redirect()->route('home');
    }
}