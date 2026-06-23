<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * SyntheticSdgController
 *
 * Computes and exposes "synthetic SDG scores" for geographic/territorial entities.
 *
 * A synthetic SDG score is a weighted average of min-max-normalized indicator values,
 * grouped per SDG (Sustainable Development Goal) number or per thematic dimension
 * (Persones, Planeta, Prosperitat, Pau).
 *
 * Supported entity types (via the `type` query parameter):
 *  - 'municipality' (default) — scores computed from municipality_value rows
 *  - 'comarca'                — scores computed from comarca_value rows
 *  - 'aggregation'            — scores computed from aggregation_value rows (future)
 *
 * The computation is implemented as a single multi-CTE SQL query. The CTE pipeline is:
 *  1. LatestYear             — find the most recent year of data per indicator
 *  2. ComputedValues         — apply the indicator's calculation method (simple/ratio/difference)
 *  3. QuartilesPerIndicator  — compute quartile ranks for IQR-based winsorization
 *  4. Q1_Q3_PerIndicator     — extract Q1 and Q3 boundaries per indicator
 *  5. WinsorizedComputedValues — cap outliers to [Q1−k·IQR, Q3+k·IQR]
 *  6. IndicatorMinMax        — find min/max of winsorized values for normalization anchors
 *  7. NormalizedValues       — min-max normalize each value to 0–100 (with sign-aware inversion)
 *  8. AggregatedValues       — weighted average per entity per SDG/dimension
 *
 * All types share the same CTE pipeline. Differences between types are handled by
 * getDataSourceConfig(), which maps each type to its concrete table names and column
 * expressions, always aliased to the canonical names used throughout the pipeline.
 */
class SyntheticSdgController
{
    /** Allowed values for the `type` query parameter. */
    private const VALID_TYPES = ['municipality', 'comarca', 'aggregation'];

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Public HTTP endpoints
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * GET /api/synthetic-sdg
     *
     * Returns the synthetic SDG score(s) for one or all entities of a given type.
     *
     * Scores are computed by normalizing each indicator to a 0–100 scale across all
     * entities (after winsorization), then taking the weighted average per SDG/dimension.
     *
     * When `global=true`, both SDG-level and dimension-level scores are returned (merged).
     *
     * Query parameters:
     *  - type              string  Entity type: 'municipality' (default), 'comarca', 'aggregation'
     *  - municipality_code string  5-digit code — filters to a single municipality (type=municipality)
     *  - comarca_code      string  Numeric code — filters to a single comarca (type=comarca)
     *  - aggregation_slug  string  Slug — filters to a single aggregation (type=aggregation)
     *  - sdg               int     Filter to a specific SDG number (optional)
     *  - global            bool    If true, also return dimension-level scores (default: false)
     *  - factor            float   IQR winsorization multiplier, k in [Q1−k·IQR, Q3+k·IQR] (default: 1.5)
     */
    #[Route('/api/synthetic-sdg', name: 'get_synthetic_sdg', methods: ['GET'])]
    public function getSyntheticSdg(Request $request): JsonResponse
    {
        $type = $request->query->get('type', 'municipality');

        if (!in_array($type, self::VALID_TYPES, true)) {
            return new JsonResponse([
                'error' => 'Invalid type. Allowed values: ' . implode(', ', self::VALID_TYPES),
            ], 400);
        }

        $entityCode = $this->extractAndSanitizeEntityCode($request, $type);
        $sdg        = preg_replace('/[^0-9]/', '', $request->query->get('sdg', '') ?? '');
        $global     = filter_var($request->query->get('global'), FILTER_VALIDATE_BOOLEAN);
        $factor     = $this->sanitizeNumeric($request->query->get('factor'), '1.5');

        $result = $this->getSyntheticSdgByCriteria($entityCode, $sdg, $global, $factor, $type);

        return new JsonResponse($result);
    }

    /**
     * GET /api/sdg-indicators
     *
     * Returns raw (non-normalized) indicator values for all municipalities for a given SDG.
     * Useful for data inspection and debugging without the normalization/weighting layer.
     *
     * Note: This endpoint only supports the 'municipality' type.
     *
     * Query parameters:
     *  - sdg  int  Required. SDG number to filter by.
     */
    #[Route('/api/sdg-indicators', name: 'get_indicators', methods: ['GET'])]
    public function getNormalizedIndicators(Request $request): JsonResponse
    {
        $sdg = preg_replace('/[^0-9]/', '', $request->query->get('sdg', '') ?? '');

        if (!$sdg) {
            return new JsonResponse(['error' => 'Parameter (sdg) is required'], 400);
        }

        $result = $this->getIndicatorsData($sdg);

        return new JsonResponse($result);
    }

    /**
     * GET /api/municipalities-under-weight
     *
     * Returns entities whose cumulative indicator weight falls below the minimum threshold.
     * These entities lack sufficient indicator coverage for a reliable synthetic SDG score
     * and should be flagged or excluded from visualizations.
     *
     * Supports the same `type`, entity code, SDG, and weight parameters as /api/synthetic-sdg.
     */
    #[Route('/api/municipalities-under-weight', name: 'get_munis_under_weight', methods: ['GET'])]
    public function getMunisUnderWeight(Request $request): JsonResponse
    {
        $type = $request->query->get('type', 'municipality');

        if (!in_array($type, self::VALID_TYPES, true)) {
            return new JsonResponse([
                'error' => 'Invalid type. Allowed values: ' . implode(', ', self::VALID_TYPES),
            ], 400);
        }

        $entityCode = $this->extractAndSanitizeEntityCode($request, $type);
        $sdg        = preg_replace('/[^0-9]/', '', $request->query->get('sdg', '') ?? '');
        $global     = filter_var($request->query->get('global'), FILTER_VALIDATE_BOOLEAN);
        $minWeight  = $this->sanitizeNumeric($request->query->get('min_weight'), '30');

        $result = $this->getEntitiesUnderWeight($entityCode, $sdg, $global, $minWeight, $type);

        return new JsonResponse($result);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Public business logic (callable from other services/tests)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Computes the full synthetic SDG result for a given entity type and optional filters.
     *
     * When $global is true, dimension-level scores (Persones/Planeta/Prosperitat/Pau)
     * are computed in a separate query and merged after the per-SDG scores.
     *
     * @param string|null $entityCode  Entity identifier (municipality_code, comarca_code, slug…)
     *                                  Pass null or empty string to get scores for all entities.
     * @param string|null $sdg         SDG number (as string) to filter, or null for all SDGs.
     * @param bool        $global      If true, also compute and append dimension-level scores.
     * @param string      $factor      IQR winsorization multiplier k (default '1.5').
     * @param string      $type        Data source type: 'municipality', 'comarca', 'aggregation'.
     * @return array<int, array<string, mixed>>
     */
    public function getSyntheticSdgByCriteria(
        ?string $entityCode,
        ?string $sdg,
        bool $global = false,
        string $factor = '1.5',
        string $type = 'municipality'
    ): array {
        // Per-SDG synthetic scores (always computed).
        $sdgScores = $this->getData($entityCode, $sdg, false, $factor, $type);

        if (!$global) {
            return $sdgScores;
        }

        // Dimension-level scores use dimension_weight instead of weight, and group by
        // thematic pillar (Persones/Planeta/Prosperitat/Pau) instead of SDG number.
        $dimensionScores = $this->getData($entityCode, $sdg, true, $factor, $type);

        // SDG scores come first, dimension scores are appended after.
        return array_merge($sdgScores, $dimensionScores);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Data source configuration
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Returns a data source configuration array for the given entity type.
     *
     * This configuration is the only place that knows which concrete database tables
     * and columns to use for each type. All SQL query builders receive this config
     * and use it uniformly, keeping the pipeline logic type-agnostic.
     *
     * The 'select_columns' fragment always aliases its output to the canonical column
     * names used throughout the CTE chain:
     *  - municipality_code — the primary identifier used for entity filtering (kept as-is)
     *  - code              — secondary identifier used for grouping in AggregatedValues
     *  - name              — human-readable name of the entity
     *  - population        — population figure (NULL for types that don't have it)
     *
     * By aliasing all types to the same column names, the CTE pipeline is written once
     * and works for all types without any branching in the SQL itself.
     *
     * The optional 'compat_aliases' key is a SQL fragment appended to the final SELECT
     * for types that need to preserve old field names for backwards compatibility.
     * For 'municipality', the pipeline previously exposed 'municipality_code_6' and
     * 'municipality_name'; these are kept as additional output columns alongside the
     * new canonical 'code' and 'name'.
     *
     * @param  string $type  One of: 'municipality', 'comarca', 'aggregation'
     * @return array{
     *   value_table:     string,
     *   entity_table:    string,
     *   entity_fk:       string,
     *   select_columns:  string,
     *   compat_aliases:  string|null,
     * }
     */
    private function getDataSourceConfig(string $type): array
    {
        return match ($type) {
            'municipality' => [
                // Table storing one row per municipality × indicator × year.
                'value_table'    => 'municipality_value',
                // Geographic entity table.
                'entity_table'   => 'municipality',
                // FK column in value_table that references entity_table.id.
                'entity_fk'      => 'municipality_id',
                // municipality_code = filter key; code = municipality_code_6 (grouping); name = municipality_name
                'select_columns' => 'm.municipality_code, m.municipality_code_6 AS code, m.municipality_name AS name, m.population',
                // Expose old column names alongside the new ones for backwards compatibility.
                'compat_aliases' => 'code AS municipality_code_6, name AS municipality_name',
            ],
            'comarca' => [
                'value_table'    => 'comarca_value',
                'entity_table'   => 'comarca',
                'entity_fk'      => 'comarca_id',
                // comarca has no separate code_6 column; comarca_code is used for both.
                // population is NULL because comarca does not store population directly.
                'select_columns' => 'm.comarca_code AS municipality_code, m.comarca_code AS code, m.comarca_name AS name, NULL AS population',
                'compat_aliases' => null,
            ],
            'aggregation' => [
                'value_table'    => 'aggregation_value',
                'entity_table'   => 'aggregation',
                'entity_fk'      => 'aggregation_id',
                // slug is the filter key; id (as string) is the grouping code.
                // population is NULL as aggregations have no population figure.
                'select_columns' => 'm.slug AS municipality_code, CAST(m.id AS CHAR) AS code, m.name AS name, NULL AS population',
                'compat_aliases' => null,
            ],
        };
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Core SQL computation
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Executes the full 8-stage CTE pipeline and returns the aggregated synthetic scores.
     *
     * @see SyntheticSdgController class docblock for a description of each pipeline stage.
     *
     * @param string|null $entityCode  Identifier of a specific entity to filter to (or null for all).
     * @param string|null $sdg         SDG number to filter to (or null for all SDGs).
     * @param bool        $global      If true, group by dimension pillar instead of SDG number.
     * @param string      $factor      IQR winsorization multiplier k.
     * @param string      $minWeight   Unused here; present for API consistency with getEntitiesUnderWeight().
     * @param string      $type        Entity type ('municipality', 'comarca', 'aggregation').
     * @return array<int, array<string, mixed>>
     */
    private function getData(
        ?string $entityCode,
        ?string $sdg,
        bool $global = false,
        string $factor = '1.5',
        string $type = 'municipality'
    ): array {
        $config = $this->getDataSourceConfig($type);

        [$params, $types, $initialWhereClause, $whereClause, $entityFilterClause] =
            $this->buildWhereClauses($entityCode, $sdg, $global);

        // When $global=true, use dimension_weight and group by thematic pillar.
        // When $global=false, use sdg weight and group by SDG number.
        $weight = $global ? 'dimension_weight' : 'weight';
        $group  = $global ? 'dimension' : 'sdg';

        $sql = $this->buildSyntheticSdgSql(
            $config,
            $initialWhereClause,
            $whereClause,
            $entityFilterClause,
            $weight,
            $group,
            $factor
        );

        return $this->executeRawQuery($sql, $params, $types);
    }

    /**
     * Builds the full multi-CTE SQL query string for the synthetic SDG pipeline.
     *
     * The SQL is parameterised by:
     *  - $config: table/column names for the chosen entity type (from getDataSourceConfig())
     *  - $initialWhereClause: SDG/weight filter applied in LatestYear (before entity filter)
     *  - $whereClause: same SDG/weight filter applied in ComputedValues
     *  - $entityFilterClause: entity-specific filter applied AFTER normalization in NormalizedValues,
     *    so that min/max are always computed across the full entity distribution
     *  - $weight: which weight column to use ('weight' for SDG, 'dimension_weight' for global)
     *  - $group: grouping column ('sdg' or 'dimension')
     *  - $factor: IQR winsorization multiplier (injected directly into SQL arithmetic)
     *
     * @param array  $config               Data source config from getDataSourceConfig().
     * @param string $initialWhereClause   WHERE fragment for LatestYear CTE.
     * @param string $whereClause          WHERE fragment for ComputedValues CTE.
     * @param string $entityFilterClause   WHERE fragment for NormalizedValues CTE.
     * @param string $weight               Weight column name ('weight' or 'dimension_weight').
     * @param string $group                Grouping column name ('sdg' or 'dimension').
     * @param string $factor               Numeric string; used in IQR bound arithmetic.
     * @return string  Complete SQL query string.
     *
     * Output columns: {$group}, code, name, population, value
     * Plus any compat_aliases defined in $config (e.g. municipality_code_6, municipality_name).
     */
    private function buildSyntheticSdgSql(
        array $config,
        string $initialWhereClause,
        string $whereClause,
        string $entityFilterClause,
        string $weight,
        string $group,
        string $factor
    ): string {
        $valueTable    = $config['value_table'];
        $entityTable   = $config['entity_table'];
        $entityFk      = $config['entity_fk'];
        $selectCols    = $config['select_columns'];
        $compatAliases = $config['compat_aliases']
            ? ', ' . $config['compat_aliases']
            : '';

        return "
            -- Stage 1: LatestYear
            -- Find the most recent year for which each indicator has at least one value row.
            -- This ensures we always compute scores from the latest available data snapshot,
            -- even when different indicators are updated on different schedules.
            WITH LatestYear AS (
                SELECT
                    mv.indicator_id,
                    MAX(mv.year) AS max_year
                FROM {$valueTable} mv
                JOIN indicator i  ON mv.indicator_id = i.id
                JOIN target t     ON i.target_id = t.id
                JOIN {$entityTable} m ON mv.{$entityFk} = m.id
                WHERE 1 = 1
                    {$initialWhereClause}
                    AND mv.subindicator IS NULL  -- Exclude sub-indicator breakdowns; only main indicators participate
                GROUP BY mv.indicator_id
            ),

            -- Stage 2: ComputedValues
            -- Compute the raw final_value for each entity×indicator×year combination.
            --
            -- The indicator's 'calculation' field determines how value and value2 are combined:
            --   simple     → use mv.value as-is (a pre-computed rate, index, or absolute count)
            --   ratio      → mv.value / mv.value2 (e.g. numerator ÷ denominator)
            --   difference → mv.value - mv.value2 (e.g. target minus actual)
            --
            -- The 'dimension' column assigns each SDG to one of four thematic pillars used
            -- for the global (cross-SDG) synthetic score:
            --   persones    → SDGs 1–5   (People)
            --   planeta     → SDGs 6, 12–15 (Planet)
            --   prosperitat → SDGs 7–11  (Prosperity)
            --   pau         → SDGs 16–17 (Peace)
            --
            -- The entity columns are aliased to canonical names (municipality_code,
            -- municipality_name, municipality_code_6, population) so all subsequent CTEs
            -- can reference them uniformly regardless of the entity type.
            ComputedValues AS (
                SELECT
                    mv.value, mv.value2, mv.year,
                    {$selectCols},
                    i.indicator_id, i.{$weight}, i.sign, i.id, i.calculation,
                    t.sdg,
                    CASE
                        WHEN i.calculation = 'simple'     THEN mv.value
                        WHEN i.calculation = 'ratio'      THEN mv.value / NULLIF(mv.value2, 0)
                        WHEN i.calculation = 'difference' THEN mv.value - mv.value2
                        ELSE NULL
                    END AS final_value,
                    CASE
                        WHEN t.sdg IN (1, 2, 3, 4, 5)     THEN 'persones'
                        WHEN t.sdg IN (6, 12, 13, 14, 15) THEN 'planeta'
                        WHEN t.sdg IN (7, 8, 9, 10, 11)   THEN 'prosperitat'
                        WHEN t.sdg IN (16, 17)             THEN 'pau'
                        ELSE NULL
                    END AS dimension
                FROM {$valueTable} mv
                JOIN indicator i  ON mv.indicator_id = i.id
                JOIN target t     ON i.target_id = t.id
                JOIN {$entityTable} m ON mv.{$entityFk} = m.id
                -- Only process rows from the latest available year per indicator (from LatestYear)
                JOIN LatestYear ly
                    ON mv.indicator_id = ly.indicator_id
                    AND mv.year = ly.max_year
                WHERE 1 = 1
                    {$whereClause}
                    AND mv.subindicator IS NULL
            ),

            -- Stage 3: QuartilesPerIndicator
            -- Assign an NTILE(4) quartile rank (1–4) to each entity's final_value within
            -- its indicator group. Q1 is the boundary of the 1st quartile (25th percentile)
            -- and Q3 is the boundary of the 3rd quartile (75th percentile).
            -- These are used in the next stage to compute IQR-based winsorization bounds.
            QuartilesPerIndicator AS (
                SELECT
                    indicator_id,
                    final_value,
                    NTILE(4) OVER (PARTITION BY indicator_id ORDER BY final_value) AS quartile
                FROM ComputedValues
            ),

            -- Stage 4: Q1_Q3_PerIndicator
            -- Extract the Q1 and Q3 values per indicator from the quartile ranks.
            -- MAX(CASE …) is a standard SQL pivot trick to get one row per indicator.
            Q1_Q3_PerIndicator AS (
                SELECT
                    indicator_id,
                    MAX(CASE WHEN quartile = 1 THEN final_value END) AS Q1,
                    MAX(CASE WHEN quartile = 3 THEN final_value END) AS Q3
                FROM QuartilesPerIndicator
                GROUP BY indicator_id
            ),

            -- Stage 5: WinsorizedComputedValues
            -- Cap outlier final_values to the interval [Q1 − k·IQR, Q3 + k·IQR],
            -- where IQR = Q3 − Q1 and k = {$factor} (the 'factor' query parameter).
            -- Values outside these bounds are replaced by the bound itself (Winsorization),
            -- preventing a handful of extreme municipalities from skewing the 0–100 scale.
            WinsorizedComputedValues AS (
                SELECT
                    c.*,
                    q.Q1,
                    q.Q3,
                    q.Q3 + {$factor} * (q.Q3 - q.Q1) AS upper_bound,
                    q.Q1 - {$factor} * (q.Q3 - q.Q1) AS lower_bound,
                    CASE
                        WHEN c.final_value > (q.Q3 + {$factor} * (q.Q3 - q.Q1)) THEN q.Q3 + {$factor} * (q.Q3 - q.Q1)
                        WHEN c.final_value < (q.Q1 - {$factor} * (q.Q3 - q.Q1)) THEN q.Q1 - {$factor} * (q.Q3 - q.Q1)
                        ELSE c.final_value
                    END AS winsorized_value
                FROM ComputedValues c
                JOIN Q1_Q3_PerIndicator q ON c.indicator_id = q.indicator_id
            ),

            -- Stage 6: IndicatorMinMax
            -- Find the global minimum and maximum winsorized_value per indicator.
            -- These anchors are used in Stage 7 to stretch each indicator's distribution
            -- onto the full 0–100 range.
            IndicatorMinMax AS (
                SELECT
                    indicator_id,
                    MIN(winsorized_value) AS min_value,
                    MAX(winsorized_value) AS max_value
                FROM WinsorizedComputedValues
                GROUP BY indicator_id
            ),

            -- Stage 7: NormalizedValues
            -- Min-max normalize each entity's winsorized value to a 0–100 scale.
            --
            -- The indicator's 'sign' field controls directionality:
            --   sign = 1 (positive indicator): higher raw value → higher score (0=worst, 100=best)
            --   sign = 0 (negative indicator): lower raw value  → higher score (inverted scale)
            --
            -- IMPORTANT: The entity-specific filter ({$entityFilterClause}) is applied HERE,
            -- after min/max are already computed across all entities. This ensures the
            -- normalization reference distribution is always the full population of entities,
            -- not just the filtered one, so a single entity's score is comparable to all others.
            NormalizedValues AS (
                SELECT
                    w.*,
                    imm.min_value,
                    imm.max_value,
                    CASE
                        WHEN sign = 1 THEN ((winsorized_value - imm.min_value) / NULLIF(imm.max_value - imm.min_value, 0)) * 100
                        WHEN sign = 0 THEN ((imm.max_value - winsorized_value) / NULLIF(imm.max_value - imm.min_value, 0)) * 100
                        ELSE NULL
                    END AS normalized_value
                FROM WinsorizedComputedValues w
                JOIN IndicatorMinMax imm ON w.indicator_id = imm.indicator_id
                WHERE 1 = 1
                    {$entityFilterClause}
            ),

            -- Stage 8: AggregatedValues
            -- Compute the final synthetic score per entity per {$group} (SDG or dimension).
            -- Each indicator's normalized value is multiplied by its weight before averaging
            -- (weighted mean), so indicators with a higher weight contribute proportionally more.
            -- Entities with no indicators for a given SDG/dimension will not appear in this output.
            AggregatedValues AS (
                SELECT
                    {$group},
                    code,
                    name,
                    population,
                    SUM(normalized_value * ({$weight})) / SUM({$weight}) AS value
                FROM NormalizedValues
                GROUP BY {$group}, code
            )

            SELECT {$group}, code, name, population, value{$compatAliases}
            FROM AggregatedValues
            ORDER BY {$group}
        ";
    }

    /**
     * Identifies entities whose total indicator weight is below the minimum threshold.
     *
     * An entity's "total weight" for a given SDG/dimension is the sum of the weights of
     * all indicators that have data for that entity in the latest year. If this sum is below
     * $minWeight, the entity is considered to lack sufficient coverage and its synthetic score
     * should be treated with caution.
     *
     * Uses the same LatestYear and ComputedValues CTEs as the main pipeline, then groups
     * by entity and filters with HAVING SUM(weight) < $minWeight.
     *
     * @param string|null $entityCode  Filter to a specific entity (or null for all).
     * @param string|null $sdg         Filter to a specific SDG (or null for all SDGs).
     * @param bool        $global      If true, use dimension_weight; otherwise use weight.
     * @param string      $minWeight   Threshold; entities with total weight below this are returned.
     * @param string      $type        Entity type ('municipality', 'comarca', 'aggregation').
     * @return array<int, array<string, mixed>>
     */
    private function getEntitiesUnderWeight(
        ?string $entityCode,
        ?string $sdg,
        bool $global = false,
        string $minWeight = '30',
        string $type = 'municipality'
    ): array {
        $config = $this->getDataSourceConfig($type);

        [$params, $types, $initialWhereClause, $whereClause] =
            $this->buildWhereClauses($entityCode, $sdg, $global);

        $weight = $global ? 'dimension_weight' : 'weight';
        $group  = $global ? 'dimension' : 'sdg';

        $sql = $this->buildUnderWeightSql($config, $initialWhereClause, $whereClause, $weight, $group, $minWeight);

        return $this->executeRawQuery($sql, $params, $types);
    }

    /**
     * Builds the SQL query that returns entities with insufficient indicator weight coverage.
     *
     * Uses three CTEs:
     *  - LatestYear:           same as in buildSyntheticSdgSql() — finds most recent year per indicator
     *  - ComputedValues:       same as in buildSyntheticSdgSql() — collects entity×indicator rows
     *  - UnderWeightEntities:  groups by entity + SDG/dimension; returns those where total weight < threshold
     *
     * @param array  $config               Data source config from getDataSourceConfig().
     * @param string $initialWhereClause   WHERE fragment for LatestYear.
     * @param string $whereClause          WHERE fragment for ComputedValues.
     * @param string $weight               Weight column name.
     * @param string $group                Grouping column name.
     * @param string $minWeight            Numeric string threshold for the HAVING clause.
     * @return string  Complete SQL query string.
     */
    private function buildUnderWeightSql(
        array $config,
        string $initialWhereClause,
        string $whereClause,
        string $weight,
        string $group,
        string $minWeight
    ): string {
        $valueTable    = $config['value_table'];
        $entityTable   = $config['entity_table'];
        $entityFk      = $config['entity_fk'];
        $selectCols    = $config['select_columns'];
        $compatAliases = $config['compat_aliases']
            ? ', ' . $config['compat_aliases']
            : '';

        return "
            WITH LatestYear AS (
                SELECT
                    mv.indicator_id,
                    MAX(mv.year) AS max_year
                FROM {$valueTable} mv
                JOIN indicator i  ON mv.indicator_id = i.id
                JOIN target t     ON i.target_id = t.id
                JOIN {$entityTable} m ON mv.{$entityFk} = m.id
                WHERE 1 = 1
                    {$initialWhereClause}
                    AND mv.subindicator IS NULL
                GROUP BY mv.indicator_id
            ),
            ComputedValues AS (
                SELECT
                    mv.value, mv.value2, mv.year,
                    {$selectCols},
                    i.indicator_id, i.{$weight}, i.sign, i.id, i.calculation,
                    t.sdg,
                    CASE
                        WHEN i.calculation = 'simple'     THEN mv.value
                        WHEN i.calculation = 'ratio'      THEN mv.value / NULLIF(mv.value2, 0)
                        WHEN i.calculation = 'difference' THEN mv.value - mv.value2
                        ELSE NULL
                    END AS final_value,
                    CASE
                        WHEN t.sdg IN (1, 2, 3, 4, 5)     THEN 'persones'
                        WHEN t.sdg IN (6, 12, 13, 14, 15) THEN 'planeta'
                        WHEN t.sdg IN (7, 8, 9, 10, 11)   THEN 'prosperitat'
                        WHEN t.sdg IN (16, 17)             THEN 'pau'
                        ELSE NULL
                    END AS dimension
                FROM {$valueTable} mv
                JOIN indicator i  ON mv.indicator_id = i.id
                JOIN target t     ON i.target_id = t.id
                JOIN {$entityTable} m ON mv.{$entityFk} = m.id
                JOIN LatestYear ly
                    ON mv.indicator_id = ly.indicator_id
                    AND mv.year = ly.max_year
                WHERE 1 = 1
                    {$whereClause}
                    AND mv.subindicator IS NULL
            ),
            -- Identify entities where the sum of available indicator weights is below the threshold.
            -- These entities do not have enough indicator coverage for a reliable synthetic score.
            UnderWeightEntities AS (
                SELECT
                    {$group},
                    code,
                    SUM({$weight}) AS total_weight
                FROM ComputedValues
                GROUP BY {$group}, code
                HAVING SUM({$weight}) < {$minWeight}
            )
            SELECT {$group}, code, total_weight{$compatAliases}
            FROM UnderWeightEntities
            ORDER BY {$group}
        ";
    }

    /**
     * Returns raw indicator values for all municipalities for a given SDG, without any
     * normalization or weighting. Used by the /api/sdg-indicators diagnostic endpoint.
     *
     * Only supports the 'municipality' type; other types are not currently needed here.
     *
     * @param string|null $sdg  SDG number to filter by (required).
     * @return array<int, array<string, mixed>>
     */
    private function getIndicatorsData(?string $sdg): array
    {
        $params             = [];
        $types              = [];
        $initialWhereClause = '';

        if ($sdg) {
            $initialWhereClause .= ' AND t.sdg = :sdg';
            $params['sdg'] = $sdg;
            $types['sdg']  = \PDO::PARAM_STR;
        }

        $sql = "
            -- Find the latest year per indicator in the municipality dataset.
            WITH LatestYear AS (
                SELECT
                    mv.indicator_id,
                    MAX(mv.year) AS max_year
                FROM municipality_value mv
                JOIN indicator i ON mv.indicator_id = i.id
                JOIN target t    ON i.target_id = t.id
                WHERE 1 = 1
                    {$initialWhereClause}
                    AND mv.subindicator IS NULL
                GROUP BY mv.indicator_id
            )
            -- Return raw value rows for the latest year only; no normalization applied.
            SELECT
                mv.value, mv.value2, mv.year,
                m.municipality_code, m.municipality_name, m.municipality_code_6, m.population,
                i.indicator_id,
                t.sdg
            FROM municipality_value mv
            JOIN indicator i ON mv.indicator_id = i.id
            JOIN target t    ON i.target_id = t.id
            JOIN municipality m ON mv.municipality_id = m.id
            JOIN LatestYear ly
                ON mv.indicator_id = ly.indicator_id
                AND mv.year = ly.max_year
        ";

        return $this->executeRawQuery($sql, $params, $types);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Query building helpers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Builds the three WHERE clause fragments and their corresponding PDO parameters.
     *
     * Returns a 5-element indexed array: [$params, $types, $initialWhereClause, $whereClause, $entityFilterClause]
     *
     * The three WHERE fragments serve different stages of the pipeline:
     *
     * $initialWhereClause (used in LatestYear CTE):
     *   Restricts which indicator years are considered "latest". Includes SDG and weight filters
     *   so we only look at years where the relevant indicators actually have data.
     *
     * $whereClause (used in ComputedValues CTE):
     *   Same SDG/weight conditions, applied to the main value rows. Ensures only indicators
     *   with a non-zero weight participate in the computation.
     *
     * $entityFilterClause (used in NormalizedValues CTE):
     *   Filters to a specific entity by its canonical 'municipality_code' column (which is
     *   aliased appropriately for non-municipality types in getDataSourceConfig()).
     *   Applied AFTER min/max calculation so the normalization reference distribution
     *   is always global, not limited to the filtered entity.
     *
     * @param string|null $entityCode  Entity identifier to filter by (or null/empty for all entities).
     * @param string|null $sdg         SDG number to filter by (or null for all SDGs).
     * @param bool        $global      If true, add dimension_weight > 0 filter; otherwise weight > 0.
     * @return array{0: array, 1: array, 2: string, 3: string, 4: string}
     */
    private function buildWhereClauses(?string $entityCode, ?string $sdg, bool $global): array
    {
        $params = [];
        $types  = [];

        $initialWhereClause = '';
        $whereClause        = '';
        $entityFilterClause = '';

        if ($entityCode) {
            // 'municipality_code' is the canonical alias used in the CTE chain for all types.
            // For comarca, this refers to the aliased comarca_code. See getDataSourceConfig().
            $entityFilterClause  .= ' AND municipality_code = :entityCode';
            $params['entityCode'] = $entityCode;
            $types['entityCode']  = \PDO::PARAM_STR;
        }

        if ($sdg) {
            $whereClause        .= ' AND t.sdg = :sdg';
            $initialWhereClause .= ' AND t.sdg = :sdg';
            $params['sdg'] = $sdg;
            $types['sdg']  = \PDO::PARAM_STR;
        }

        if ($global) {
            // Dimension-level computation: only indicators with a non-zero dimension_weight participate.
            $whereClause        .= ' AND i.dimension_weight > 0';
            $initialWhereClause .= ' AND i.dimension_weight > 0';
        } else {
            // SDG-level computation: only indicators with a non-zero (SDG) weight participate.
            $whereClause        .= ' AND i.weight > 0';
            $initialWhereClause .= ' AND i.weight > 0';
        }

        return [$params, $types, $initialWhereClause, $whereClause, $entityFilterClause];
    }

    /**
     * Extracts the entity code from the request and sanitizes it based on entity type.
     *
     * Each type reads from a different query parameter name and applies sanitization
     * appropriate to the expected format of that code:
     *  - municipality: reads 'municipality_code', strips non-digits (codes are 5-digit strings)
     *  - comarca:      reads 'comarca_code', strips non-digits (codes are numeric strings)
     *  - aggregation:  reads 'aggregation_slug', strips anything not in [a-z0-9-] (URL-safe slugs)
     *
     * Returns an empty string if the corresponding parameter is absent.
     *
     * @param Request $request  The current HTTP request.
     * @param string  $type     One of the VALID_TYPES.
     * @return string  Sanitized entity code, or '' if not provided.
     */
    private function extractAndSanitizeEntityCode(Request $request, string $type): string
    {
        return match ($type) {
            'municipality' => preg_replace('/[^0-9]/', '', $request->query->get('municipality_code', '') ?? ''),
            'comarca'      => preg_replace('/[^0-9]/', '', $request->query->get('comarca_code', '') ?? ''),
            'aggregation'  => preg_replace('/[^a-z0-9\-]/', '', $request->query->get('aggregation_slug', '') ?? ''),
        };
    }

    /**
     * Returns $value if it is numeric, otherwise returns $default.
     *
     * Used to safely sanitize the 'factor' and 'min_weight' query parameters,
     * which are injected directly into SQL arithmetic expressions.
     *
     * @param string|null $value    Raw value from the query string.
     * @param string      $default  Fallback value if $value is not numeric.
     * @return string
     */
    private function sanitizeNumeric(?string $value, string $default): string
    {
        return is_numeric($value) ? $value : $default;
    }

    /**
     * Executes a raw SQL query via the Doctrine DBAL connection and returns all result rows.
     *
     * Uses Doctrine's low-level connection rather than the ORM so that we can write
     * multi-CTE queries that span multiple tables without mapping to entities.
     *
     * @param string                    $sql     Raw SQL query string with named placeholders.
     * @param array<string, mixed>      $params  Named parameter values (e.g. ['sdg' => '3']).
     * @param array<string, int>        $types   PDO type constants per parameter (e.g. ['sdg' => PDO::PARAM_STR]).
     * @return array<int, array<string, mixed>>  All result rows as associative arrays.
     */
    private function executeRawQuery(string $sql, array $params, array $types): array
    {
        $conn = $this->entityManager->getConnection();
        $stmt = $conn->executeQuery($sql, $params, $types);

        return $stmt->fetchAllAssociative();
    }
}
