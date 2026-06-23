<?php

namespace App\Service\Aggregation;

use App\Config\AggregationConfig;
use App\Entity\Indicator;
use Doctrine\DBAL\Connection;

/**
 * Handles indicators listed in AggregationConfig::AVERAGE_INDICATORS.
 *
 * Stores AVG(value) across all municipalities in the group, for every year
 * present in municipality_value (subindicator IS NULL).
 * Indicators listed in ROUNDED_AVERAGE_INDICATORS use ROUND(AVG(value)) instead,
 * keeping the result on the original discrete scale.
 */
class AverageStrategy implements AggregationStrategyInterface
{
    public function __construct(
        private readonly Connection $connection,
    ) {}

    public function supports(Indicator $indicator): bool
    {
        return in_array($indicator->getIndicatorId(), AggregationConfig::AVERAGE_INDICATORS, true);
    }

    /**
     * @return array<int, array{year: int, value: float}>
     */
    public function calculate(Indicator $indicator, GroupContext $group): array
    {
        $rounded = in_array($indicator->getIndicatorId(), AggregationConfig::ROUNDED_AVERAGE_INDICATORS, true);
        $avgExpr = $rounded ? 'ROUND(AVG(mv.value))' : 'AVG(mv.value)';

        $sql = "
            SELECT mv.year, {$avgExpr} AS avg_value
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
                'year'  => (int) $row['year'],
                'value' => (float) $row['avg_value'],
            ];
        }

        return $results;
    }
}
