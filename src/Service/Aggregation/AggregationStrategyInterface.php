<?php

namespace App\Service\Aggregation;

use App\Entity\Indicator;

interface AggregationStrategyInterface
{
    /**
     * Returns true if this strategy can handle the given indicator.
     */
    public function supports(Indicator $indicator): bool;

    /**
     * Calculates aggregated values for one indicator + group (aggregation or comarca),
     * one entry per year available in the source municipality_value data.
     *
     * Returns an array of ['year' => int, 'value' => float, 'value2' => float (optional)] rows.
     * Returns an empty array if the calculation is not possible (e.g. missing data).
     */
    public function calculate(Indicator $indicator, GroupContext $group): array;
}
