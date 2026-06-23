<?php

namespace App\Service\Aggregation;

use App\Config\AggregationConfig;
use App\Entity\Indicator;
use Doctrine\DBAL\Connection;

/**
 * Handles indicators listed in AggregationConfig::POPULATION_WEIGHTED_INDICATORS.
 *
 * Formula: SUM(mv.value * pop.population_count) / SUM(pop.population_count)
 * across all municipalities in the group, for every year present in
 * municipality_value (subindicator IS NULL).
 * The same formula is applied to value2 when present (only rows that have
 * value2 contribute to its weighted denominator).
 *
 * Population per year is read from the `population` table (imported from
 * Transparència Catalunya API via app:import-population).
 */
class PopulationWeightedStrategy implements AggregationStrategyInterface
{
    public function __construct(
        private readonly Connection $connection,
    ) {}

    public function supports(Indicator $indicator): bool
    {
        return in_array($indicator->getIndicatorId(), AggregationConfig::POPULATION_WEIGHTED_INDICATORS, true);
    }

    /**
     * @return array<int, array{year: int, value: float, value2?: float}>
     */
    public function calculate(Indicator $indicator, GroupContext $group): array
    {
        $sql = "
            SELECT mv.year,
                   SUM(mv.value * pop.population_count)                                        AS weighted_sum,
                   SUM(CASE WHEN mv.value2 IS NOT NULL THEN mv.value2 * pop.population_count END) AS weighted_sum2,
                   SUM(pop.population_count)                                                    AS total_population,
                   SUM(CASE WHEN mv.value2 IS NOT NULL THEN pop.population_count END)           AS total_population2
            FROM municipality_value mv
            {$group->memberJoinSql}
            JOIN population pop
                ON  pop.municipality_id = mv.municipality_id
                AND pop.year            = mv.year
            WHERE mv.indicator_id = :indicatorId
              AND mv.subindicator IS NULL
              AND pop.population_count > 0
            GROUP BY mv.year
        ";

        $stmt = $this->connection->executeQuery($sql, [
            'groupId'     => $group->id,
            'indicatorId' => $indicator->getId(),
        ], [
            'groupId'     => \PDO::PARAM_INT,
            'indicatorId' => \PDO::PARAM_INT,
        ]);

        $results = [];

        foreach ($stmt->fetchAllAssociative() as $row) {
            $totalPopulation = (float) $row['total_population'];

            if ($totalPopulation == 0.0) {
                continue;
            }

            $entry = [
                'year'  => (int) $row['year'],
                'value' => (float) $row['weighted_sum'] / $totalPopulation,
            ];

            $totalPopulation2 = $row['total_population2'] !== null ? (float) $row['total_population2'] : 0.0;
            if ($row['weighted_sum2'] !== null && $totalPopulation2 > 0.0) {
                $entry['value2'] = (float) $row['weighted_sum2'] / $totalPopulation2;
            }

            $results[] = $entry;
        }

        return $results;
    }
}
