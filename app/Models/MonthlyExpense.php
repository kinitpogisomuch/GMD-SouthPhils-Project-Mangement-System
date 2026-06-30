<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonthlyExpense extends Model
{
    protected $fillable = ['month_year', 'category', 'description', 'amount'];

    protected $casts = ['amount' => 'decimal:2'];

    public static function categories(): array
    {
        return [
            'Electricity', 'Water', 'Internet', 'Rent',
            'Equipment Rental', 'Fuel & Transportation',
            'Office Supplies', 'Maintenance & Repair', 'Other',
        ];
    }

    public static function totalForMonth(string $monthYear): float
    {
        return (float) self::where('month_year', $monthYear)->sum('amount');
    }
}
