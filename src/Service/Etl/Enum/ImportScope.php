<?php

namespace App\Service\Etl\Enum;

enum ImportScope: string
{
    case Municipality = 'municipality';
    case Comarca = 'comarca';
    case Province = 'province';
    case Aggregation = 'aggregation';

    public static function fromCsv(string $csv): array
    {
        return array_map(fn ($s) => self::from(trim($s)), explode(',', $csv));
    }
}
