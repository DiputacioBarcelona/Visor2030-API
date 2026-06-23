<?php

namespace App\Service\Aggregation;

use App\Config\AggregationConfig;
use App\Entity\Indicator;
use Doctrine\DBAL\Connection;

/**
 * Handles indicators listed in AggregationConfig::RATIO_INDICATORS.
 *
 * Stores SUM(value) and SUM(value2) across all municipalities in the group,
 * for every year present in municipality_value (subindicator IS NULL).
 */
class RatioAggregationStrategy implements AggregationStrategyInterface
{
    public function __construct(
        private readonly Connection $connection,
    ) {}

    public function supports(Indicator $indicator): bool
    {
        return in_array($indicator->getIndicatorId(), AggregationConfig::RATIO_INDICATORS, true);
    }

    /**
     * @return array<int, array{year: int, value: float, value2: float}>
     */
    public function calculate(Indicator $indicator, GroupContext $group): array
    {
        $sql = "
            SELECT mv.year, SUM(mv.value) AS sum_value, SUM(mv.value2) AS sum_value2
            FROM municipality_value mv
            {$group->memberJoinSql}
            WHERE mv.indicator_id = :indicatorId
              AND mv.subindicator IS NULL
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
            $results[] = [
                'year'   => (int) $row['year'],
                'value'  => (float) $row['sum_value'],
                'value2' => (float) $row['sum_value2'],
            ];
        }

        return $results;
    }
}
