# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

## [Unreleased] - 2026-03-27

### Added
- `AggregationValue` entity — mirrors `MunicipalityValue` structure; linked to `Aggregation` and `Indicator` via ManyToOne; exposed via `GET /api/aggregation_values` with filters on `aggregation`, `aggregation.slug`, `indicator.indicator_id`, and `year`.
- `AggregationValueRepository` — standard service entity repository.
- `AggregationConfig` — central config listing indicators eligible for aggregation calculation, grouped by strategy type (`RATIO_INDICATORS`, `POPULATION_WEIGHTED_INDICATORS`).
- `AggregationStrategyInterface` — contract for pluggable aggregation calculation strategies (`supports()` / `calculate()`).
- `GroupContext` DTO — abstracts the target group (Aggregation or Comarca) as a SQL JOIN fragment, making strategies group-type-agnostic.
- `RatioAggregationStrategy` — calculates `SUM(value) / SUM(value2)` per year across municipalities in a group; handles indicators listed in `AggregationConfig::RATIO_INDICATORS`.
- `AggregationCalculatorService` — dispatcher that resolves the correct strategy for an indicator and writes upserted `AggregationValue` or `ComarcaValue` rows; entry points `calculateForAggregation()` and `calculateForComarca()`.
- `Municipality` endpoint: `?aggregations.slug=<slug>` filter to retrieve municipalities belonging to a specific aggregation.
- Migration `Version20260327105154` — creates `aggregation_value` table with FKs to `aggregation` and `indicator`.
- `SyntheticSdgController`: `type` query parameter (`municipality` | `comarca` | `aggregation`) to select the data source for synthetic SDG computation. Defaults to `municipality` for backwards compatibility.
- `SyntheticSdgController`: `comarca_code` query parameter — filters synthetic SDG results to a single comarca when `type=comarca`.
- `SyntheticSdgController`: `aggregation_slug` query parameter — prepared for future `type=aggregation` support.
- `SyntheticSdgController`: comarca support on `/api/synthetic-sdg` and `/api/municipalities-under-weight` — scores are now computed from `comarca_value` rows when `type=comarca`.
- `SyntheticSdgController`: `getDataSourceConfig()` — maps each entity type to its concrete table names, canonical column aliases (`code`, `name`), and an optional `compat_aliases` fragment for backwards-compatible output field names.
- `SyntheticSdgController`: `buildSyntheticSdgSql()` — extracted and fully documented the 8-stage CTE pipeline (LatestYear → ComputedValues → Winsorization → Normalization → AggregatedValues).
- `SyntheticSdgController`: `buildUnderWeightSql()` — extracted the under-weight identification query into its own builder.
- `SyntheticSdgController`: `buildWhereClauses()` — consolidates WHERE fragment construction (previously duplicated across two methods).
- `SyntheticSdgController`: `executeRawQuery()` — small helper wrapping Doctrine DBAL `executeQuery` + `fetchAllAssociative`.
- `SyntheticSdgController`: `extractAndSanitizeEntityCode()` — centralises per-type query parameter reading and sanitisation.
- `SyntheticSdgController`: `sanitizeNumeric()` — helper for safe sanitisation of `factor` and `min_weight` parameters.
- `docs/synthetic-sdg-controller-refactor.md` — implementation notes documenting the refactor, method changes, and how to add new entity types.

### Changed
- `SyntheticSdgController`: `getSyntheticSdgByCriteria()` — added `string $type = 'municipality'` parameter; removed unused `$minWeight` parameter.
- `SyntheticSdgController`: `getMunicipalitiesUnderWeight()` (private) renamed to `getEntitiesUnderWeight()` to reflect support for any entity type.
- `SyntheticSdgController`: API response fields for `/api/synthetic-sdg` and `/api/municipalities-under-weight` now include `code` and `name` as the canonical entity identifier fields. For `type=municipality`, the previous fields `municipality_code_6` and `municipality_name` are still returned alongside them for backwards compatibility.

### Fixed
- `SyntheticSdgController`: PDO type hints (`$types` array) were being overwritten instead of merged when both an entity code and an SDG were provided simultaneously — the type hint for the first bound parameter was silently lost.

### Removed
- `SyntheticSdgController`: `groupData()` — was a passthrough (`return $results`) with no effect; grouping is handled entirely by the `AggregatedValues` CTE.
- `SyntheticSdgController`: unused imports (`MunicipalityValue`, `Target`, `IndicatorCalculator`) and the unused `$minMaxCache` class property.
