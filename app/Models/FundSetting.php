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

    /** The fund is set up once its starting capital (initial balance) has been entered. */
    public static function isInitialized(): bool
    {
        return (float) static::instance()->initial_balance > 0;
    }

    /** Additional capital invested after the initial setup — each addition is its own entry in the fund history. */
    public static function totalTopUps(): float
    {
        return (float) FundTransaction::where('type', 'topup')->sum('amount');
    }

    /**
     * Total Balance, worked out from the records rather than trusted from a stored figure:
     *   initial balance + additional capital − fund released to projects + repaid by projects
     */
    public static function ledgerBalance(): float
    {
        return round(
            (float) static::instance()->initial_balance
            + static::totalTopUps()
            - FundTransaction::totalReleased()
            + FundTransaction::totalReplenished(),
            2
        );
    }

    /** Keeps the stored current balance (used for locking when money is released) equal to the records. */
    public static function syncBalance(): float
    {
        $setting = static::instance();
        $ledger  = static::ledgerBalance();
        if (abs((float) $setting->current_balance - $ledger) >= 0.005) {
            $setting->update(['current_balance' => $ledger]);
        }
        return $ledger;
    }

    /**
     * Sets the starting capital. Only while the fund has no history yet (first setup, or fixing a typo
     * before anything happened) — after that the initial balance is a fixed record, and more money is
     * added as additional capital instead, so the two are never mixed up.
     */
    public static function setInitialBalance(float $newInitial): array
    {
        if (static::isInitialized() && FundTransaction::exists()) {
            return ['ok' => false, 'message' => 'The initial balance is already set and the fund is in use, so it can no longer be changed. Use Add Capital to put more money into the fund.'];
        }

        $setting = static::instance();
        $setting->update([
            'initial_balance' => $newInitial,
            'current_balance' => $newInitial,
        ]);

        return ['ok' => true];
    }

    /**
     * Adds money to the fund and records it in the fund history. Runs in one transaction with the
     * balance row locked, so the balance and the history change together or not at all.
     */
    public static function topUp(float $amount, ?string $note, string $recordedBy): FundTransaction
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($amount, $note, $recordedBy) {
            $setting = static::query()->lockForUpdate()->first() ?? static::instance();
            if ((float) $setting->initial_balance <= 0) {
                throw new \DomainException('Set up the fund\'s initial balance first.');
            }

            $newBalance = round(static::ledgerBalance() + $amount, 2);
            $purpose    = 'Additional capital' . ($note ? ' — ' . $note : '');

            $entry = FundTransaction::create([
                'type'          => 'topup',
                'amount'        => round($amount, 2),
                'date'          => now()->format('Y-m-d'),
                'project_id'    => null,
                'purpose'       => $purpose,
                'description'   => $purpose,
                'remarks'       => $note,
                'status'        => 'Completed',
                'balance_after' => $newBalance,
                'recorded_by'   => $recordedBy,
                'source_type'   => 'topup',
            ]);

            $setting->update(['current_balance' => $newBalance]);

            return $entry;
        });
    }
}
