<?php

namespace App\Support;

/**
 * Peso amounts written out the way they go on a receipt:
 *   57500     → "Fifty-Seven Thousand Five Hundred Pesos Only"
 *   1250.50   → "One Thousand Two Hundred Fifty Pesos and 50/100"
 */
class AmountInWords
{
    private const ONES = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten',
        'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
    private const TENS = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];
    private const SCALES = ['', 'Thousand', 'Million', 'Billion'];

    public static function pesos(float $amount): string
    {
        $amount   = round(abs($amount), 2);
        $whole    = (int) floor($amount);
        $centavos = (int) round(($amount - $whole) * 100);

        $words = $whole === 0 ? 'Zero' : self::words($whole);
        $words .= $whole === 1 ? ' Peso' : ' Pesos';

        return $centavos > 0
            ? $words . ' and ' . str_pad((string) $centavos, 2, '0', STR_PAD_LEFT) . '/100'
            : $words . ' Only';
    }

    private static function words(int $n): string
    {
        $parts = [];
        foreach (self::SCALES as $scale) {
            $chunk = $n % 1000;
            if ($chunk > 0) {
                array_unshift($parts, trim(self::hundreds($chunk) . ' ' . $scale));
            }
            $n = intdiv($n, 1000);
            if ($n === 0) {
                break;
            }
        }

        return implode(' ', $parts);
    }

    private static function hundreds(int $n): string
    {
        $out = [];
        if ($n >= 100) {
            $out[] = self::ONES[intdiv($n, 100)] . ' Hundred';
            $n %= 100;
        }
        if ($n >= 20) {
            $out[] = self::TENS[intdiv($n, 10)] . ($n % 10 ? '-' . self::ONES[$n % 10] : '');
        } elseif ($n > 0) {
            $out[] = self::ONES[$n];
        }

        return implode(' ', $out);
    }
}
