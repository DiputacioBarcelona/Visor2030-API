<?php

namespace App\Service\Aggregation;

use App\Config\AggregationConfig;
use App\Entity\Indicator;
use Doctrine\DBAL\Connection;

/**
 * Handles indicator 14.2.1 only.
 *
 * Formula: SUM(mv.value * km_coast) / SUM(km_coast)
 * across all municipalities in the group, for every year present in
 * municipality_value (subindicator IS NULL).
 *
 * Coastline lengths are loaded from public/uploads/km_costa_municipis_cat.csv.
 * CODIMUNI in the CSV is the 6-digit INE code (Barcelona municipalities appear
 * without their leading "0"), matched against municipality.municipality_code_6.
 *
 * Municipalities absent from the CSV are excluded from the weighted calculation —
 * they do not contribute to numerator or denominator.
 */
class CoastalAggregationStrategy implements AggregationStrategyInterface
{
    /** @var array<string, float>|null municipality_code_6 => km_coast */
    private ?array $kmCosta = null;

    public function __construct(
        private readonly Connection $connection,
        private readonly string $projectDir,
    ) {}

    public function supports(Indicator $indicator): bool
    {
        return in_array($indicator->getIndicatorId(), AggregationConfig::COASTAL_WEIGHTED_INDICATORS, true);
    }

    /**
     * @return array<int, array{year: int, value: float, value2?: float}>
     */
    public function calculate(Indicator $indicator, GroupContext $group): array
    {
        $kmCosta = $this->loadKmCosta();

        if (empty($kmCosta)) {
            return [];
        }

        $sql = "
            SELECT mv.year, mv.value, mv.value2, m.municipality_code_6
            FROM municipality_value mv
            {$group->memberJoinSql}
            JOIN municipality m ON m.id = mv.municipality_id
            WHERE mv.indicator_id = :indicatorId
              AND mv.subindicator IS NULL
            ORDER BY mv.year
        ";

        $stmt = $this->connection->executeQuery($sql, [
            'groupId'     => $group->id,
            'indicatorId' => $indicator->getId(),
        ], [
            'groupId'     => \PDO::PARAM_INT,
            'indicatorId' => \PDO::PARAM_INT,
        ]);

        // Accumulate per year: [year => [weighted_sum, total_km, weighted_sum2, total_km2]]
        $byYear = [];

        foreach ($stmt->fetchAllAssociative() as $row) {
            $code   = $row['municipality_code_6'];
            $km     = $kmCosta[$code] ?? 0.0;

            if ($km <= 0.0) {
                continue;
            }

            $year  = (int) $row['year'];
            $value = (float) $row['value'];

            if (!isset($byYear[$year])) {
                $byYear[$year] = [
                    'weighted_sum'  => 0.0,
                    'total_km'      => 0.0,
                    'weighted_sum2' => 0.0,
                    'total_km2'     => 0.0,
                ];
            }

            $byYear[$year]['weighted_sum'] += $value * $km;
            $byYear[$year]['total_km']     += $km;

            if ($row['value2'] !== null) {
                $byYear[$year]['weighted_sum2'] += (float) $row['value2'] * $km;
                $byYear[$year]['total_km2']     += $km;
            }
        }

        $results = [];

        foreach ($byYear as $year => $acc) {
            if ($acc['total_km'] <= 0.0) {
                continue;
            }

            $entry = [
                'year'  => $year,
                'value' => $acc['weighted_sum'] / $acc['total_km'],
            ];

            if ($acc['total_km2'] > 0.0) {
                $entry['value2'] = $acc['weighted_sum2'] / $acc['total_km2'];
            }

            $results[] = $entry;
        }

        return $results;
    }

    /**
     * Loads coastline lengths from the CSV, keyed by municipality_code_6.
     *
     * @return array<string, float>
     */
    private function loadKmCosta(): array
    {
        if ($this->kmCosta !== null) {
            return $this->kmCosta;
        }

        $path   = $this->projectDir . '/public/uploads/km_costa_municipis_cat.csv';
        $handle = fopen($path, 'r');

        if ($handle === false) {
            return $this->kmCosta = [];
        }

        $header = fgetcsv($handle);
        $map    = [];

        while (($row = fgetcsv($handle)) !== false) {
            $data           = array_combine($header, $row);
            $code6          = str_pad($data['CODIMUNI'], 6, '0', STR_PAD_LEFT);
            $map[$code6]    = (float) str_replace(',', '.', $data['km_costa']);
        }

        fclose($handle);

        return $this->kmCosta = $map;
    }
}
