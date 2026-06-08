<?php

namespace App\Models;

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
    ];

    protected $hidden = ['password'];

    protected $casts = [
        'password'    => 'hashed',
        'first_login' => 'boolean',
    ];

    public function setFirstLoginAttribute($value): void
    {
        $this->attributes['first_login'] = $value ? 'true' : 'false';
    }

    /** "Dela Cruz, Juan" — for table display */
    public function getFullNameAttribute(): string
    {
        if ($this->first_name && $this->last_name) {
            return trim($this->last_name . ', ' . $this->first_name);
        }
        return $this->name ?? '';
    }
}