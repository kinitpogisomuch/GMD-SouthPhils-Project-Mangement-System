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
    ];

    protected $hidden = ['password', 'email_verification_token'];

    protected $casts = [
        'password'                   => 'hashed',
        'first_login'                => PostgresBoolean::class,
        'email_verified_at'          => 'datetime',
        'email_verification_sent_at' => 'datetime',
    ];

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