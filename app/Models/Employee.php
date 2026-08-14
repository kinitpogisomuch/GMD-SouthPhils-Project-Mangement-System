<?php

namespace App\Models;

use App\Casts\PostgresBoolean;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

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
        'first_login'      => PostgresBoolean::class,
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

    public function assignedProjects()
    {
        return $this->belongsToMany(Project::class, 'project_employee')->withTimestamps();
    }

    /** Next "EGMD-XXXX" username that would be assigned to a new employee */
    public static function nextUsername(): string
    {
        $row = DB::selectOne(
            "SELECT COALESCE(MAX(CAST(SUBSTRING(username FROM 6) AS INTEGER)), 0) AS max_num
             FROM employees WHERE username ~ '^EGMD-[0-9]+$'"
        );
        $nextNum = (int) ($row->max_num ?? 0) + 1;
        do {
            $username = 'EGMD-' . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
            if (!self::where('username', $username)->exists()) {
                return $username;
            }
            $nextNum++;
        } while (true);
    }
}