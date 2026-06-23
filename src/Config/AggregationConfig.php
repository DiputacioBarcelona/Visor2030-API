<?php

namespace App\Config;

class AggregationConfig
{
    /**
     * Indicators whose aggregated value is semantically correct as SUM(value)/SUM(value2).
     * List provided and maintained by the team.
     */
    public const RATIO_INDICATORS = [
        '1.2.3',
        '1.3.1',
        '1.4.1',
        '1.4.2',
        '1.5.1',
        '2.1.1',
        '2.3.1',
        '2.3.2',
        '2.3.3',
        '2.3.4',
        '3.4.1',
        '3.6.1',
        '4.a.1',
        '4.1.1',
        '4.1.2',
        '4.2.1',
        '4.4.1',
        '4.4.2',
        '4.4.3',
        '4.4.4',
        '4.5.1',
        '5.5.1',
        '5.c.1',
        '5.c.2',
        '6.4.1',
        '7.2.1',
        '7.3.1',
        '8.3.1',
        '8.3.2',
        '8.3.4',
        '8.5.1',
        '8.5.2',
        '8.9.1',
        '9.2.1',
        '9.5.1',
        '11.2.1',
        '11.3.1',
        '11.4.2',
        '11.7.1',
        '12.1.1',
        '12.5.1',
        '12.5.2',
        '13.2.1',
        '15.4.1',
        '16.7.1',
        '17.1.1',
        '17.1.2',
        '17.2.1',
        '17.17.1',
        // ... full list to be completed by team
    ];

    /**
     * Ratio indicators for comarca target only — excludes those already populated
     * from another source (see RATIO_INDICATORS for the full aggregation list).
     */
    public const COMARCA_RATIO_INDICATORS = [
        '1.2.3',
        '1.4.1',
        '1.5.1',
        '2.1.1',
        '2.3.1',
        '3.6.1',
        '4.1.1',
        '4.1.2',
        '4.2.1',
        '4.a.1',
        '5.5.1',
        '6.4.1',
        '7.2.1',
        '7.3.1',
        '8.3.2',
        '8.3.4',
        '9.5.1',
        '11.2.1',
        '11.3.1',
        '11.4.2',
        '11.7.1',
        '12.1.1',
        '12.5.1',
        '12.5.2',
        '15.4.1',
        '16.7.1',
        '17.1.1',
        '17.1.2',
        '17.2.1',
        '17.17.1',
    ];

    /**
     * Ratio indicators for province target only.
     */
    public const PROVINCE_RATIO_INDICATORS = [
        '1.2.3',
        '1.4.1',
        '1.5.1',
        '2.1.1',
        '2.3.1',
        '3.6.1',
        '4.1.1',
        '4.1.2',
        '4.2.1',
        '4.a.1',
        '5.5.1',
        '6.4.1',
        '7.2.1',
        '7.3.1',
        '8.3.2',
        '8.3.4',
        '9.5.1',
        '11.2.1',
        '11.3.1',
        '11.4.2',
        '11.7.1',
        '12.1.1',
        '12.5.1',
        '12.5.2',
        '15.4.1',
        '16.7.1',
        '17.1.1',
        '17.1.2',
        '17.2.1',
        '17.17.1',
    ];

    /**
     * Indicators whose aggregated value is a population-weighted average.
     * Formula: SUM(value * population) / SUM(population)
     * Population per year is read from the `population` table
     * (imported via app:import-population from Transparència Catalunya API).
     */
    public const POPULATION_WEIGHTED_INDICATORS = [
        '1.1.1',
        '1.2.1',
        '1.2.2',
        '3.4.2',
        '5.1.1',
        '6.1.1',
        '7.1.1',
        '8.2.1',
        '8.3.3',
        '9.c.1',
        '10.1.2',
        '10.1.3',
        '10.1.4',
        '10.4.1',
        '10.4.2',
        '11.1.1',
        '11.3.2',
        '13.2.2',
        '15.1.1',
        '15.1.2',
        '15.2.1',
        '16.6.1',
        '16.10.1',
        // ... full list to be completed by team
    ];

    /**
     * Population-weighted indicators for comarca target only — excludes those already
     * populated from another source (see POPULATION_WEIGHTED_INDICATORS for the full list).
     */
    public const COMARCA_POPULATION_WEIGHTED_INDICATORS = [
        '1.1.1',
        '1.2.1',
        '6.1.1',
        '9.c.1',
        '10.1.2',
        '10.1.3',
        '10.4.1',
        '10.4.2',
        '11.1.1',
        '11.3.2',
        '13.2.2',
        '15.1.1',
        '15.1.2',
        '15.2.1',
        '16.6.1',
        '16.10.1',
    ];

    /**
     * Population-weighted indicators for province target only.
     */
    public const PROVINCE_POPULATION_WEIGHTED_INDICATORS = [
        '1.1.1',
        '1.2.1',
        '6.1.1',
        '9.c.1',
        '10.1.2',
        '10.1.3',
        '10.4.1',
        '10.4.2',
        '11.1.1',
        '11.3.2',
        '13.2.2',
        '15.1.1',
        '15.1.2',
        '15.2.1',
        '16.6.1',
        '16.10.1',
    ];

    /**
     * Indicators whose aggregated value is a simple mean of municipality values.
     * Formula: AVG(value) per year, optionally rounded (see ROUNDED_AVERAGE_INDICATORS).
     */
    public const AVERAGE_INDICATORS = ['13.1.1'];

    /** Average indicators for comarca target only. */
    public const COMARCA_AVERAGE_INDICATORS = ['13.1.1'];

    /** Average indicators for province target only. */
    public const PROVINCE_AVERAGE_INDICATORS = ['13.1.1'];

    /**
     * Subset of AVERAGE_INDICATORS where the mean is rounded to the nearest integer.
     * Use when source values are on a discrete ordinal scale (e.g. 1–4).
     */
    public const ROUNDED_AVERAGE_INDICATORS = ['13.1.1'];

    /**
     * Indicator 14.1.1 only: weighted average by number of beaches per municipality.
     * Beach counts are fetched at runtime from the Transparència Catalunya open-data API.
     */
    public const BEACHES_WEIGHTED_INDICATORS = [
        '14.1.1',
    ];

    /** Beaches-weighted indicators for comarca target only. */
    public const COMARCA_BEACHES_WEIGHTED_INDICATORS = [
        '14.1.1',
    ];

    /** Beaches-weighted indicators for province target only. */
    public const PROVINCE_BEACHES_WEIGHTED_INDICATORS = [
        '14.1.1',
    ];

    /**
     * Indicator 14.2.1 only: weighted average by km of coastline per municipality.
     * Coastline lengths are loaded from public/uploads/km_costa_municipis_cat.csv.
     */
    public const COASTAL_WEIGHTED_INDICATORS = [
        '14.2.1',
    ];

    /** Coastal-weighted indicators for comarca target only. */
    public const COMARCA_COASTAL_WEIGHTED_INDICATORS = [
        '14.2.1',
    ];

    /** Coastal-weighted indicators for province target only. */
    public const PROVINCE_COASTAL_WEIGHTED_INDICATORS = [
        '14.2.1',
    ];

    public const STRATEGY_RATIO               = 'ratio';
    public const STRATEGY_POPULATION_WEIGHTED = 'population-weighted';
    public const STRATEGY_BEACHES_WEIGHTED    = 'beaches-weighted';
    public const STRATEGY_COASTAL_WEIGHTED    = 'coastal-weighted';
    public const STRATEGY_AVERAGE             = 'average';

    /**
     * Returns all indicator_ids eligible for aggregation (non-comarca) calculation.
     */
    public static function getAllEligibleIndicators(): array
    {
        return array_merge(
            self::RATIO_INDICATORS,
            self::POPULATION_WEIGHTED_INDICATORS,
            self::BEACHES_WEIGHTED_INDICATORS,
            self::COASTAL_WEIGHTED_INDICATORS,
            self::AVERAGE_INDICATORS,
        );
    }

    /**
     * Returns all indicator_ids eligible for comarca calculation (excludes those
     * already populated from another source).
     */
    public static function getAllEligibleComarcaIndicators(): array
    {
        return array_merge(
            self::COMARCA_RATIO_INDICATORS,
            self::COMARCA_POPULATION_WEIGHTED_INDICATORS,
            self::COMARCA_BEACHES_WEIGHTED_INDICATORS,
            self::COMARCA_COASTAL_WEIGHTED_INDICATORS,
            self::COMARCA_AVERAGE_INDICATORS,
        );
    }

    /**
     * Returns all indicator_ids eligible for province calculation.
     */
    public static function getAllEligibleProvinceIndicators(): array
    {
        return array_merge(
            self::PROVINCE_RATIO_INDICATORS,
            self::PROVINCE_POPULATION_WEIGHTED_INDICATORS,
            self::PROVINCE_BEACHES_WEIGHTED_INDICATORS,
            self::PROVINCE_COASTAL_WEIGHTED_INDICATORS,
            self::PROVINCE_AVERAGE_INDICATORS,
        );
    }

    /**
     * Returns the indicator_ids for a specific strategy (aggregation target).
     * Returns null if the strategy name is unknown.
     *
     * @return list<string>|null
     */
    public static function getIndicatorsForStrategy(string $strategy): ?array
    {
        return match ($strategy) {
            self::STRATEGY_RATIO => self::RATIO_INDICATORS,
            self::STRATEGY_POPULATION_WEIGHTED => self::POPULATION_WEIGHTED_INDICATORS,
            self::STRATEGY_BEACHES_WEIGHTED    => self::BEACHES_WEIGHTED_INDICATORS,
            self::STRATEGY_COASTAL_WEIGHTED    => self::COASTAL_WEIGHTED_INDICATORS,
            self::STRATEGY_AVERAGE             => self::AVERAGE_INDICATORS,
            default                            => null,
        };
    }

    /**
     * Returns the indicator_ids for a specific strategy (comarca target).
     * Returns null if the strategy name is unknown.
     *
     * @return list<string>|null
     */
    public static function getComarcaIndicatorsForStrategy(string $strategy): ?array
    {
        return match ($strategy) {
            self::STRATEGY_RATIO => self::COMARCA_RATIO_INDICATORS,
            self::STRATEGY_POPULATION_WEIGHTED => self::COMARCA_POPULATION_WEIGHTED_INDICATORS,
            self::STRATEGY_BEACHES_WEIGHTED    => self::COMARCA_BEACHES_WEIGHTED_INDICATORS,
            self::STRATEGY_COASTAL_WEIGHTED    => self::COMARCA_COASTAL_WEIGHTED_INDICATORS,
            self::STRATEGY_AVERAGE             => self::COMARCA_AVERAGE_INDICATORS,
            default                            => null,
        };
    }

    /**
     * Returns the indicator_ids for a specific strategy (province target).
     * Returns null if the strategy name is unknown.
     *
     * @return list<string>|null
     */
    public static function getProvinceIndicatorsForStrategy(string $strategy): ?array
    {
        return match ($strategy) {
            self::STRATEGY_RATIO => self::PROVINCE_RATIO_INDICATORS,
            self::STRATEGY_POPULATION_WEIGHTED => self::PROVINCE_POPULATION_WEIGHTED_INDICATORS,
            self::STRATEGY_BEACHES_WEIGHTED    => self::PROVINCE_BEACHES_WEIGHTED_INDICATORS,
            self::STRATEGY_COASTAL_WEIGHTED    => self::PROVINCE_COASTAL_WEIGHTED_INDICATORS,
            self::STRATEGY_AVERAGE             => self::PROVINCE_AVERAGE_INDICATORS,
            default                            => null,
        };
    }
}
