<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FundSetting extends Model
{
    protected $fillable = [
        'current_balance',
        'initial_balance',
    ];

    protected $casts = [
        'current_balance' => 'decimal:2',
        'initial_balance' => 'decimal:2',
    ];

    public static function instance(): self
    {
        return static::firstOrCreate([], [
            'current_balance' => 0,
            'initial_balance' => 0,
        ]);
    }

    public static function getCurrentBalance(): float
    {
        return (float) static::instance()->current_balance;
    }

    public static function getInitialBalance(): float
    {
        return (float) static::instance()->initial_balance;
    }

    public static function adjustBalance(float $delta): float
    {
        $setting = static::instance();
        $newBalance = (float) $setting->current_balance + $delta;
        $setting->update(['current_balance' => $newBalance]);

        return $newBalance;
    }

    /**
     * Set the fund's initial balance. The current balance is shifted by the
     * same delta, so this also works as a top-up/correction after the fund
     * is already in use.
     */
    public static function setInitialBalance(float $newInitial): array
    {
        $setting = static::instance();
        $delta   = $newInitial - (float) $setting->initial_balance;
        $newCurrent = (float) $setting->current_balance + $delta;

        if ($newCurrent < 0) {
            return ['ok' => false, 'message' => 'That would make the current fund balance negative.'];
        }

        $setting->update([
            'initial_balance' => $newInitial,
            'current_balance' => $newCurrent,
        ]);

        return ['ok' => true];
    }
}
