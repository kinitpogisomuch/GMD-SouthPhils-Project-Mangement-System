<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'profile_photo',
        'contact',
        'email',
        'address',
        'role',
        'employee_type',
        'daily_rate',
        'pay_type',
        'sss',
        'philhealth',
        'pagibig',
        'other_deductions',
        'date_hired',
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
        'daily_rate'       => 'decimal:2',
        'sss'              => 'decimal:2',
        'philhealth'       => 'decimal:2',
        'pagibig'          => 'decimal:2',
        'other_deductions' => 'decimal:2',
        'date_hired'       => 'date',
        'password'         => 'hashed',
        'first_login'      => 'boolean',
    ];

    /** "Nadera, Kenneth" — for table display */
    public function getFullNameAttribute(): string
    {
        return trim($this->last_name . ', ' . $this->first_name);
    }

    /** "Kenneth Nadera" — backward-compat for any code using ->name */
    public function getNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }
}