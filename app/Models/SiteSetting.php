<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    protected $fillable = [
        'phone',
        'mobile',
        'email',
        'address',
        'facebook',
        'business_hours',
        'description',
    ];

    public static function instance(): self
    {
        return static::firstOrCreate([], []);
    }
}
