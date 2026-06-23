<?php

namespace App\Service\Aggregation;

use App\Config\AggregationConfig;
use App\Entity\Indicator;
use Doctrine\DBAL\Connection;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Handles indicator 14.1.1 only.
 *
 * Formula: SUM(mv.value * beach_count) / SUM(beach_count)
 * across all municipalities in the group, for every year present in
 * municipality_value (subindicator IS NULL).
 * The same formula is applied to value2 when present.
 *
 * Beach counts are fetched at runtime from the Transparència Catalunya open-data API
 * (dataset 83qv-sib8 — coastal/beach registry). The API returns one row per
 * municipality with its ide_municipi (6-digit INE code without leading zero) and
 * the count of beaches. Prepending "0" to ide_municipi produces our 7-char
 * municipality_code.
 *
 * Municipalities with no entry in the beach registry (no beaches) are excluded
 * from the weighted calculation — they do not contribute to numerator or
 * denominator.
 */
class NumberOfBeachesWeightedStrategy implements AggregationStrategyInterface
{
    private const BEACHES_API_URL = 'https://analisi.transparenciacatalunya.cat/resource/83qv-sib8.json';
    private const BEACHES_API_QUERY = <<<'SQ'
        SELECT `ide_municipi`, count(*)
        WHERE starts_with(`ide_municipi`, "8")
        GROUP BY `ide_municipi`
        SQ;

    public function __construct(
        private readonly Connection $connection,
        private readonly HttpClientInterface $httpClient,
    ) {}

    public function supports(Indicator $indicator): bool
    {
        return in_array($indicator->getIndicatorId(), AggregationConfig::BEACHES_WEIGHTED_INDICATORS, true);
    }

    /**
     * @return array<int, array{year: int, value: float, value2?: float}>
     */
    public function calculate(Indicator $indicator, GroupContext $group): array
    {
        $beachCounts = $this->fetchBeachCounts();

        if (empty($beachCounts)) {
            return [];
        }

        $sql = "
            SELECT mv.year, mv.value, mv.value2, m.municipality_code
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

        // Accumulate per year: [year => [weighted_sum, weighted_sum2, total_beaches, total_beaches2]]
        $byYear = [];

        foreach ($stmt->fetchAllAssociative() as $row) {
            $code       = $row['municipality_code'];
            $beachCount = $beachCounts[$code] ?? 0;

            if ($beachCount <= 0) {
                continue;
            }

            $year  = (int) $row['year'];
            $value = (float) $row['value'];

            if (!isset($byYear[$year])) {
                $byYear[$year] = [
                    'weighted_sum'    => 0.0,
                    'total_beaches'   => 0,
                    'weighted_sum2'   => 0.0,
                    'total_beaches2'  => 0,
                ];
            }

            $byYear[$year]['weighted_sum']  += $value * $beachCount;
            $byYear[$year]['total_beaches'] += $beachCount;

            if ($row['value2'] !== null) {
                $byYear[$year]['weighted_sum2']  += (float) $row['value2'] * $beachCount;
                $byYear[$year]['total_beaches2'] += $beachCount;
            }
        }

        $results = [];

        foreach ($byYear as $year => $acc) {
            if ($acc['total_beaches'] === 0) {
                continue;
            }

            $entry = [
                'year'  => $year,
                'value' => $acc['weighted_sum'] / $acc['total_beaches'],
            ];

            if ($acc['total_beaches2'] > 0) {
                $entry['value2'] = $acc['weighted_sum2'] / $acc['total_beaches2'];
            }

            $results[] = $entry;
        }

        return $results;
    }

    /**
     * Fetches beach counts from the open-data API.
     *
     * @return array<string, int>  municipality_code (7-char, e.g. "0801930") => beach count
     */
    private function fetchBeachCounts(): array
    {
        $response = $this->httpClient->request('GET', self::BEACHES_API_URL, [
            'query' => ['$query' => self::BEACHES_API_QUERY],
        ]);

        $data = $response->toArray();

        $counts = [];

        foreach ($data as $row) {
            // ide_municipi starts with "8", prepend "0" to match municipality_code
            $municipalityCode = '0' . $row['ide_municipi'];
            $counts[$municipalityCode] = (int) $row['count'];
        }

        return $counts;
    }
}
