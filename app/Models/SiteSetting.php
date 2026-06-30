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
        'kpi_profit_margin_target',
        'kpi_on_time_target',
        'kpi_budget_adherence_target',
    ];

    public static function instance(): self
    {
        return static::firstOrCreate([], []);
    }
}
