<?php

namespace App\Util;

class IndicatorCalculator
{
    public static function percent($d)
    {
        if (!$d->getValue2()) {
            return null;
        }

        return ($d->getValue() * 100) / $d->getValue2();
    }

    public static function percentMonthly($d)
    {
        if (!$d->getValue2()) {
            return null;
        }

        return ($d->getValue() * 12 * 100) / $d->getValue2();
    }

    public static function perThousand($d)
    {
        if (!$d->getValue2()) {
            return null;
        }

        return ($d->getValue() * 1000) / $d->getValue2();
    }

    public static function perUnit($d)
    {
        if (!$d->getValue2()) {
            return null;
        }

        return $d->getValue() / $d->getValue2();
    }

    public static function simple($d)
    {
        return $d->getValue();
    }

    public static function simplePerHundred($d)
    {
        return $d->getValue() * 100;
    }

    public static function simplePerThousand($d)
    {
        return $d->getValue() * 1000;
    }

    public static function perTenThousand($d)
    {
        if (!$d->getValue2()) {
            return null;
        }

        return ($d->getValue() * 10000) / $d->getValue2();
    }

    public static function perHundredThousand($d)
    {
        if (!$d->getValue2()) {
            return null;
        }

        return ($d->getValue() * 100000) / $d->getValue2();
    }

    public static function diffPercent($d)
    {
        if (!($d->getValue() + $d->getValue2())) {
            return null;
        }

        return (($d->getValue() - $d->getValue2()) * 100) / ($d->getValue() + $d->getValue2());
    }

    public static function diff($d)
    {
        return $d->getValue() - $d->getValue2();
    }

    public static function baixaAlta($d)
    {
        $levels = [1 => 'LOW', 2 => 'MEDIUM', 3 => 'HIGH', 4 => 'VERY_HIGH'];

        return $levels[$d->getValue()] ?? 'UNKNOWN';
    }

    public static function siNo($d)
    {
        return 1 == $d->getValue() ? 'SI' : (0 == $d->getValue() ? 'NO' : 'UNKNOWN');
    }

    public static function actualitzat($d)
    {
        return 1 == $d->getValue() ? 'ACTUALITZAT' : (0 == $d->getValue() ? 'NO_ACTUALITZAT' : 'UNKNOWN');
    }

    // create var indicators in the class
    public const INDICATORS = [
        '1.1.1' => [
            'calculation' => [self::class, 'simple'],
            'decimals' => 1,
            'decimals1' => 1,
            'decimals2' => 0,
        ],
        '1.2.1' => [
            'calculation' => [self::class, 'simple'],
            'decimals' => 2,
            'decimals1' => 2,
            'decimals2' => 0,
        ],
        '1.2.2' => [
            'calculation' => [self::class, 'simple'],
            'decimals' => 0,
            'decimals1' => 0,
            'decimals2' => 0,
        ],
        '1.2.3' => [
            'calculation' => [self::class, 'perThousand'],
            'decimals' => 2,
            'decimals1' => 0,
            'decimals2' => 0,
        ],
        '1.3.1' => [
            'calculation' => [self::class, 'perTenThousand'],
            'decimals' => 2,
            'decimals1' => 0,
            'decimals2' => 0,
        ],
        '1.4.1' => [
            'calculation' => [self::class, 'percent'],
            'decimals' => 2,
            'decimals1' => 0,
            'decimals2' => 1,
        ],
        '1.4.2' => [
            'calculation' => [self::class, 'percent'],
            'decimals' => 2,
            'decimals1' => 0,
            'decimals2' => 0,
        ],
        '1.5.1' => [
            'calculation' => [self::class, 'perTenThousand'],
            'decimals' => 2,
            'decimals1' => 0,
            'decimals2' => 0,
        ],
        '2.1.1' => [
            'calculation' => [self::class, 'percent'],
            'decimals' => 2,
            'decimals1' => 2,
            'decimals2' => 2,
        ],
        '2.3.1' => [
            'calculation' => [self::class, 'percent'],
            'decimals' => 2,
            'decimals1' => 2,
            'decimals2' => 0,
        ],
        '2.3.2' => [
            'calculation' => [self::class, 'percent'],
            'decimals' => 2,
            'decimals1' => 1,
            'decimals2' => 1,
        ],
        '2.3.3' => [
            'calculation' => [self::class, 'percent'],
            'decimals' => 2,
            'decimals1' => 0,
            'decimals2' => 0,
            // 'subindicators' => [0, 2],
        ],
        '2.3.4' => [
            'calculation' => [self::class, 'percent'],
            'decimals' => 2,
            'decimals1' => 0,
            'decimals2' => 0,
            // 'subindicators' => [0, 2],
        ],
        '3.4.1' => [
            'calculation' => [self::class, 'percent'],
            'decimals' => 2,
            'decimals1' => 0,
            'decimals2' => 0,
        ],
        '3.4.2' => [
            'calculation' => [self::class, 'simple'],
            'decimals' => 2,
            'decimals1' => 2,
            'decimals2' => 0,
        ],
        '3.6.1' => [
            'calculation' => [self::class, 'perTenThousand'],
            'decimals' => 2,
            'decimals1' => 0,
            'decimals2' => 0,
        ],
        '4.1.1' => [
            'calculation' => [self::class, 'percent'],
            'decimals' => 2,
            'decimals1' => 0,
            'decimals2' => 0,
        ],
        '4.1.2' => [
            'calculation' => [self::class, 'percent'],
            'decimals' => 2,
            'decimals1' => 0,
            'decimals2' => 0,
        ],
        '4.2.1' => [
            'calculation' => [self::class, 'percent'],
            'decimals' => 2,
            'decimals1' => 0,
            'decimals2' => 0,
        ],
        '4.4.1' => [
            'calculation' => [self::class, 'percent'],
            'decimals' => 2,
            'decimals1' => 0,
            'decimals2' => 0,
            // 'subindicators' => [0, 2, 3, 4],
        ],
        '4.4.2' => [
            'calculation' => [self::class, 'percent'],
            'decimals' => 2,
            'decimals1' => 0,
            'decimals2' => 0,
        ],
        '4.4.3' => [
            'calculation' => [self::class, 'percent'],
            'decimals' => 2,
            'decimals1' => 0,
            'decimals2' => 0,
        ],
        '4.4.4' => [
            'calculation' => [self::class, 'percent'],
            'decimals' => 2,
            'decimals1' => 0,
            'decimals2' => 0,
        ],
        '4.5.1' => [
            'calculation' => [self::class, 'perUnit'],
            'decimals' => 2,
            'decimals1' => 0,
            'decimals2' => 0,
        ],
        '4.a.1' => [
            'calculation' => [self::class, 'perUnit'],
            'decimals' => 2,
            'decimals1' => 0,
            'decimals2' => 0,
        ],
        '5.1.1' => [
            'calculation' => [self::class, 'diff'],
            'decimals' => 2,
            'decimals1' => 1,
            'decimals2' => 1,
        ],
        '5.5.1' => [
            'calculation' => [self::class, 'percent'],
            'decimals' => 2,
            'decimals1' => 0,
            'decimals2' => 0,
        ],
        '5.c.1' => [
            'calculation' => [self::class, 'simple'],
            'decimals' => 1,
            'decimals1' => 1,
            'decimals2' => 0,
        ],
        '5.c.2' => [
            'calculation' => [self::class, 'simple'],
            'decimals' => 1,
            'decimals1' => 1,
            'decimals2' => 0,
        ],
        '6.1.1' => [
            'calculation' => [self::class, 'simple'],
            'decimals' => 3,
            'decimals1' => 3,
            'decimals2' => 0,
        ],
        '6.4.1' => [
            'calculation' => [self::class, 'perUnit'],
            'decimals' => 2,
            'decimals1' => 0,
            'decimals2' => 0,
        ],
        '7.1.1' => [
            'calculation' => [self::class, 'percent'],
            'decimals' => 2,
            'decimals1' => 2,
            'decimals2' => 2,
        ],
        '7.2.1' => [
            'calculation' => [self::class, 'percent'],
            'decimals' => 2,
            'decimals1' => 1,
            'decimals2' => 1,
        ],
        '7.3.1' => [
            'calculation' => [self::class, 'perUnit'],
            'decimals' => 2,
            'decimals1' => 1,
            'decimals2' => 0,
        ],
        '8.2.1' => [
            'calculation' => [self::class, 'simplePerThousand'],
            'decimals' => 0,
            'decimals1' => 1,
            'decimals2' => 0,
        ],
        '8.3.1' => [
            'calculation' => [self::class, 'percent'],
            'decimals' => 2,
            'decimals1' => 0,
            'decimals2' => 0,
        ],
        '8.3.2' => [
            'calculation' => [self::class, 'perThousand'],
            'decimals' => 2,
            'decimals1' => 0,
            'decimals2' => 0,
        ],
        '8.3.3' => [
            'calculation' => [self::class, 'simple'],
            'decimals' => 1,
            'decimals1' => 1,
            'decimals2' => 0,
        ],
        '8.3.4' => [
            'calculation' => [self::class, 'percent'],
            'decimals' => 2,
            'decimals1' => 0,
            'decimals2' => 0,
        ],
        '8.5.1' => [
            'calculation' => [self::class, 'percent'],
            'decimals' => 2,
            'decimals1' => 1,
            'decimals2' => 0,
        ],
        '8.5.2' => [
            'calculation' => [self::class, 'percent'],
            'decimals' => 2,
            'decimals1' => 1,
            'decimals2' => 0,
        ],
        '8.9.1' => [
            'calculation' => [self::class, 'perTenThousand'],
            'decimals' => 2,
            'decimals1' => 0,
            'decimals2' => 0,
        ],
        '9.1.1' => [
            'calculation' => [self::class, 'simplePerHundred'],
            'decimals' => 2,
            'decimals1' => 2,
            'decimals2' => 0,
        ],
        '9.2.1' => [
            'calculation' => [self::class, 'percent'],
            'decimals' => 2,
            'decimals1' => 0,
            'decimals2' => 0,
        ],
        '9.5.1' => [
            'calculation' => [self::class, 'perTenThousand'],
            'decimals' => 2,
            'decimals1' => 0,
            'decimals2' => 0,
        ],
        '9.5.2' => [
            'calculation' => [self::class, 'percent'],
            'decimals' => 2,
            'decimals1' => 0,
            'decimals2' => 0,
        ],
        '9.8.1' => [
            'calculation' => [self::class, 'simplePerHundred'],
            'decimals' => 2,
            'decimals1' => 2,
            'decimals2' => 0,
        ],
        '10.1.1' => [
            'calculation' => [self::class, 'simple'],
            'decimals' => 1,
            'decimals1' => 1,
            'decimals2' => 0,
        ],
        '10.1.2' => [
            'calculation' => [self::class, 'simple'],
            'decimals' => 1,
            'decimals1' => 1,
            'decimals2' => 0,
        ],
        '10.1.3' => [
            'calculation' => [self::class, 'simple'],
            'decimals' => 1,
            'decimals1' => 1,
            'decimals2' => 0,
        ],
        '10.1.4' => [
            'calculation' => [self::class, 'simple'],
            'decimals' => 0,
            'decimals1' => 0,
            'decimals2' => 0,
        ],
        '10.4.1' => [
            'calculation' => [self::class, 'simple'],
            'decimals' => 2,
            'decimals1' => 2,
            'decimals2' => 0,
            // 'subindicators' => [0, 2],
        ],
        '10.4.2' => [
            'calculation' => [self::class, 'simple'],
            'decimals' => 2,
            'decimals1' => 2,
            'decimals2' => 0,
            // 'subindicators' => [0, 2],
        ],
        '11.1.1' => [
            'calculation' => [self::class, 'percentMonthly'],
            'decimals' => 2,
            'decimals1' => 2,
            'decimals2' => 0,
        ],
        '11.2.1' => [
            'calculation' => [self::class, 'perTenThousand'],
            'decimals' => 2,
            'decimals1' => 0,
            'decimals2' => 0,
        ],
        '11.3.1' => [
            'calculation' => [self::class, 'percent'],
            'decimals' => 2,
            'decimals1' => 2,
            'decimals2' => 2,
        ],
        '11.3.2' => [
            'calculation' => [self::class, 'simple'],
            'decimals' => 2,
            'decimals1' => 2,
            'decimals2' => 0,
        ],
        '11.4.1' => [
            'calculation' => [self::class, 'perUnit'],
            'decimals' => 2,
            'decimals1' => 2,
            'decimals2' => 0,
        ],
        '11.4.2' => [
            'calculation' => [self::class, 'perTenThousand'],
            'decimals' => 2,
            'decimals1' => 0,
            'decimals2' => 0,
        ],
        '11.6.1' => [
            'calculation' => [self::class, 'perThousand'],
            'decimals' => 2,
            'decimals1' => 2,
            'decimals2' => 0,
        ],
        '11.6.2' => [
            'calculation' => [self::class, 'percent'],
            'decimals' => 2,
            'decimals1' => 2,
            'decimals2' => 2,
        ],
        '11.7.1' => [
            'calculation' => [self::class, 'perUnit'],
            'decimals' => 2,
            'decimals1' => 2,
            'decimals2' => 0,
        ],
        '12.1.1' => [
            'calculation' => [self::class, 'perHundredThousand'],
            'decimals' => 2,
            'decimals1' => 0,
            'decimals2' => 0,
        ],
        // '12.2.1' => [
        //     'calculation' => [self::class, 'perUnit'],
        //     'decimals' => 2,
        //     'decimals1' => 2,
        //     'decimals2' => 0,
        // ],
        '12.5.1' => [
            'calculation' => [self::class, 'percent'],
            'decimals' => 2,
            'decimals1' => 2,
            'decimals2' => 2,
        ],
        '12.5.2' => [
            'calculation' => [self::class, 'perThousand'],
            'decimals' => 2,
            'decimals1' => 2,
            'decimals2' => 0,
        ],
        '13.1.1' => [
            'calculation' => [self::class, 'simple'],
            'textFormat' => [self::class, 'baixaAlta'],
            'decimals' => 0,
            'decimals1' => 0,
            'decimals2' => 0,
        ],
        '13.2.1' => [
            'calculation' => [self::class, 'perUnit'],
            'decimals' => 2,
            'decimals1' => 2,
            'decimals2' => 0,
        ],
        '13.2.2' => [
            'calculation' => [self::class, 'simple'],
            'decimals' => 2,
            'decimals1' => 2,
            'decimals2' => 0,
        ],
        '14.1.1' => [
            'calculation' => [self::class, 'simple'],
            'decimals' => 2,
            'decimals1' => 2,
            'decimals2' => 0,
        ],
        '14.2.1' => [
            'calculation' => [self::class, 'simple'],
            'decimals' => 2,
            'decimals1' => 2,
            'decimals2' => 0,
        ],
        '15.1.1' => [
            'calculation' => [self::class, 'simple'],
            'decimals' => 2,
            'decimals1' => 2,
            'decimals2' => 0,
        ],
        '15.1.2' => [
            'calculation' => [self::class, 'simple'],
            'decimals' => 2,
            'decimals1' => 2,
            'decimals2' => 0,
        ],
        '15.2.1' => [
            'calculation' => [self::class, 'simple'],
            'decimals' => 2,
            'decimals1' => 2,
            'decimals2' => 0,
        ],
        '15.4.1' => [
            'calculation' => [self::class, 'percent'],
            'decimals' => 2,
            'decimals1' => 2,
            'decimals2' => 2,
        ],
        '16.6.1' => [
            'calculation' => [self::class, 'simple'],
            'decimals' => 2,
            'decimals1' => 0,
            'decimals2' => 0,
        ],
        '16.7.1' => [
            'calculation' => [self::class, 'percent'],
            'decimals' => 2,
            'decimals1' => 0,
            'decimals2' => 0,
        ],
        '16.7.2' => [
            'calculation' => [self::class, 'simple'],
            'textFormat' => [self::class, 'actualitzat'],
            'decimals' => 0,
            'decimals1' => 0,
            'decimals2' => 0,
        ],
        '16.10.1' => [
            'calculation' => [self::class, 'simple'],
            'decimals' => 1,
            'decimals1' => 1,
            'decimals2' => 0,
        ],
        '17.1.1' => [
            'calculation' => [self::class, 'perUnit'],
            'decimals' => 2,
            'decimals1' => 2,
            'decimals2' => 0,
        ],
        '17.1.2' => [
            'calculation' => [self::class, 'perUnit'],
            'decimals' => 2,
            'decimals1' => 2,
            'decimals2' => 0,
        ],
        '17.2.1' => [
            'calculation' => [self::class, 'perUnit'],
            'decimals' => 2,
            'decimals1' => 2,
            'decimals2' => 0,
        ],
        '17.17.1' => [
            'calculation' => [self::class, 'perHundredThousand'],
            'decimals' => 2,
            'decimals1' => 0,
            'decimals2' => 0,
        ],
        
        
        '17.17.2' => [
            'calculation' => [self::class, 'perHundredThousand'],
            'decimals' => 2,
            'decimals1' => 0,
            'decimals2' => 0,
        ],
        
    ];
}
