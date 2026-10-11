<?php

namespace App\Models;

use App\Casts\PostgresBoolean;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $fillable = [
        'name',
        'first_name',
        'last_name',
        'profile_photo',
        'address',
        'contact',
        'email',
        'status',
        'username',
        'password',
        'first_login',
        'credentials_sent_at',
        'region',
        'province',
        'city',
        'barangay',
        'street_address',
        'password_updated_at',
        'rejection_reason',
        'email_verified_at',
        'email_verification_token',
        'email_verification_sent_at',
        'account_setup_token',
        'account_setup_sent_at',
    ];

    protected $hidden = ['password', 'email_verification_token', 'account_setup_token'];

    protected $casts = [
        'password'                   => 'hashed',
        'first_login'                => PostgresBoolean::class,
        'email_verified_at'          => 'datetime',
        'email_verification_sent_at' => 'datetime',
        'account_setup_sent_at'      => 'datetime',
    ];

    /*
     | Registration lifecycle for self-registered clients (status + email_verified_at):
     |   Pending (email unverified) → Pending Email Verification
     |   Pending (email verified)   → Pending Admin Approval
     |   Approved                   → Approved – Account Setup Required (sets their password via the emailed link)
     |   Active                     → can log in
     |   Rejected                   → declined by the admin
     */
    public function registrationStatusLabel(): string
    {
        return match ($this->status) {
            'Pending'  => $this->hasVerifiedEmail() ? 'Pending Admin Approval' : 'Pending Email Verification',
            'Approved' => 'Approved – Account Setup Required',
            'Inactive' => 'Archived',
            default    => $this->status,
        };
    }

    /**
     * Username for a self-registered client: "CGMD-" + exactly four random digits (e.g. CGMD-4827),
     * the same CGMD-XXXX shape as admin-created clients. Checked against clients (unique index),
     * employees and admin users so it can never collide with another login. Assigned once at
     * registration and never changed.
     */
    public static function generateRegistrationUsername(): string
    {
        for ($attempt = 0; $attempt < 200; $attempt++) {
            $username = 'CGMD-' . str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);

            $taken = self::where('username', $username)->exists()
                || \DB::table('employees')->where('username', $username)->exists()
                || \DB::table('users')->where('username', $username)->exists();

            if (!$taken) {
                return $username;
            }
        }

        throw new \RuntimeException('Could not generate a unique client username.');
    }

    /** Email status (separate from account approval): Unverified → Verified */
    public function hasVerifiedEmail(): bool
    {
        return $this->email_verified_at !== null;
    }

    /** Clients the admin can act on: verified sign-ups awaiting approval, plus every non-pending account */
    public function scopeVisibleToAdmin($query)
    {
        return $query->where(function ($q) {
            $q->where('status', '!=', 'Pending')->orWhereNotNull('email_verified_at');
        });
    }

    /** Pending Approval + email verified — what the admin's approval queue and badges count */
    public function scopeAwaitingApproval($query)
    {
        return $query->where('status', 'Pending')->whereNotNull('email_verified_at');
    }

    /** "Dela Cruz, Juan" — for table display */
    public function getFullNameAttribute(): string
    {
        if ($this->first_name && $this->last_name) {
            return trim($this->last_name . ', ' . $this->first_name);
        }
        return $this->name ?? '';
    }

    /** Next unused CGMD-XXXX username (sequential, gap-filling) */
    public static function nextAvailableUsername(): string
    {
        $row = \DB::selectOne(
            "SELECT COALESCE(MAX(CAST(SUBSTRING(username FROM 6) AS INTEGER)), 0) AS max_num
             FROM clients WHERE username ~ '^CGMD-[0-9]+$'"
        );

        $nextNum = (int) ($row->max_num ?? 0) + 1;

        do {
            $username = 'CGMD-' . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
            if (!self::where('username', $username)->exists()) {
                return $username;
            }
            $nextNum++;
        } while (true);
    }
}