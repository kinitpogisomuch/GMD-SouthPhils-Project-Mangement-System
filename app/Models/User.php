<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    protected $fillable = [
        'full_name',
        'profile_photo',
        'username',
        'email',
        'password',
        'role',
        'phone',
        'position',
        'address',
        'status',
        'first_login',
        'region',
        'province',
        'city',
        'barangay',
        'street_address',
        'password_updated_at',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'password'    => 'hashed',
        'first_login' => 'boolean',
    ];

    public function setFirstLoginAttribute($value): void
    {
        $this->attributes['first_login'] = $value ? 'true' : 'false';
    }
}