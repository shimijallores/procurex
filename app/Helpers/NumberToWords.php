<?php

declare(strict_types=1);

namespace App\Helpers;

class NumberToWords
{
    private const ONES = [
        0 => 'Zero', 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four',
        5 => 'Five', 6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine',
        10 => 'Ten', 11 => 'Eleven', 12 => 'Twelve', 13 => 'Thirteen',
        14 => 'Fourteen', 15 => 'Fifteen', 16 => 'Sixteen', 17 => 'Seventeen',
        18 => 'Eighteen', 19 => 'Nineteen',
    ];

    private const TENS = [
        2 => 'Twenty', 3 => 'Thirty', 4 => 'Forty', 5 => 'Fifty',
        6 => 'Sixty', 7 => 'Seventy', 8 => 'Eighty', 9 => 'Ninety',
    ];

    public static function convert(float $amount, string $format = 'pesos'): string
    {
        $amount = max(0, round($amount, 2));
        $whole = (int) floor($amount);
        $cents = (int) round(($amount - $whole) * 100);

        $wholeWords = self::convertWhole($whole);

        if ($whole === 0 && $cents === 0) {
            return 'Zero Pesos Only';
        }

        $result = $wholeWords.' Pesos';

        if ($format === 'centavos') {
            if ($cents > 0) {
                $centsWords = self::convertWhole($cents);
                $result .= ' and '.$centsWords.' Centavos';
            }
        } else {
            if ($cents > 0) {
                $result .= ' and '.str_pad((string) $cents, 2, '0', STR_PAD_LEFT).'/100 Only';
            } else {
                $result .= ' Only';
            }
        }

        return $result;
    }

    private static function convertWhole(int $number): string
    {
        if ($number < 20) {
            return self::ONES[$number];
        }

        if ($number < 100) {
            $tens = (int) floor($number / 10);
            $ones = $number % 10;

            return self::TENS[$tens].($ones > 0 ? ' '.self::ONES[$ones] : '');
        }

        if ($number < 1000) {
            $hundreds = (int) floor($number / 100);
            $remainder = $number % 100;

            return self::ONES[$hundreds].' Hundred'.($remainder > 0 ? ' '.self::convertWhole($remainder) : '');
        }

        if ($number < 1000000) {
            $thousands = (int) floor($number / 1000);
            $remainder = $number % 1000;

            return self::convertWhole($thousands).' Thousand'.($remainder > 0 ? ' '.self::convertWhole($remainder) : '');
        }

        if ($number < 1000000000) {
            $millions = (int) floor($number / 1000000);
            $remainder = $number % 1000000;

            return self::convertWhole($millions).' Million'.($remainder > 0 ? ' '.self::convertWhole($remainder) : '');
        }

        $billions = (int) floor($number / 1000000000);
        $remainder = $number % 1000000000;

        return self::convertWhole($billions).' Billion'.($remainder > 0 ? ' '.self::convertWhole($remainder) : '');
    }
}
