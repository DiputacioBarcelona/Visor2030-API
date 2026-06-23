<?php

namespace App\Service\Etl\Importer;

use App\Config\AggregationConfig;
use App\Entity\Comarca;
use App\Entity\Indicator;
use App\Entity\Municipality;
use App\Entity\Province;
use App\Service\AggregationCalculatorService;
use App\Service\Etl\Dto\EtlContext;
use App\Service\Etl\Dto\IndicatorDefinition;
use App\Service\Etl\Enum\ImportScope;
use App\Service\Etl\Geo\GeoRegistry;
use App\Service\Etl\Indicator\IndicatorFactory;
use App\Service\Etl\Persistence\ValuePersister;
use App\Service\Etl\Persistence\ValueWriteResult;
use App\Service\Etl\Source\DoClient;
use App\Service\Etl\Source\IdescatJsonClient;
use App\Service\Etl\Source\IdescatTableClient;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

abstract class AbstractEtlImporter
{
    protected int $created = 0;
    protected int $updated = 0;
    protected int $unchanged = 0;
    protected int $skipped = 0;

    private ?EtlContext $currentContext = null;

    public function __construct(
        protected readonly EntityManagerInterface $em,
        protected readonly HttpClientInterface $http,
        protected readonly LoggerInterface $logger,
        protected readonly GeoRegistry $geo,
        protected readonly IndicatorFactory $indicatorFactory,
        protected readonly ValuePersister $values,
        protected readonly IdescatJsonClient $idescatJson,
        protected readonly IdescatTableClient $idescatTable,
        protected readonly DoClient $do,
        protected readonly AggregationCalculatorService $aggregations,
    ) {
    }

    // === Public contract ===

    final public function getSupportedIndicatorIds(): array
    {
        return array_keys($this->getDefinitions());
    }

    final public function supports(string $indicatorId): bool
    {
        return isset($this->getDefinitions()[$indicatorId]);
    }

    final public function run(string $indicatorId, EtlContext $context): bool
    {
        $defs = $this->getDefinitions();
        if (!isset($defs[$indicatorId])) {
            throw new \InvalidArgumentException(sprintf('Importer %s does not support indicator %s.', static::class, $indicatorId));
        }

        $definition = $defs[$indicatorId];
        $this->resetCounters();
        $this->currentContext = $context;
        $started = microtime(true);
        $shortName = (new \ReflectionClass($this))->getShortName();

        $scopeLabel = $context->scopes === null
            ? 'all'
            : implode(',', array_map(fn (ImportScope $s) => $s->value, $context->scopes));

        $this->logger->info(sprintf('ETL %s starting [%s] · scopes=%s', $indicatorId, $shortName, $scopeLabel));

        try {
            // Ensure Target + Indicator rows exist before any value rows are written.
            // Common to every importer — lives here once instead of in each import().
            $target = $this->indicatorFactory->getOrCreateTarget($definition);
            $indicator = $this->indicatorFactory->getOrCreateIndicator($definition, $target);

            if ($this->shouldImport(ImportScope::Municipality)) {
                $this->logger->info(sprintf('ETL %s → importing municipalities…', $indicatorId));
            }

            $this->import($definition, $indicator, $context);

            $this->em->flush();
            $this->afterSuccess($definition, $context);

            $this->logger->info(sprintf(
                'ETL %s done [%s] · created=%d updated=%d unchanged=%d skipped=%d in %.2fs',
                $indicatorId,
                $shortName,
                $this->created,
                $this->updated,
                $this->unchanged,
                $this->skipped,
                microtime(true) - $started
            ));

            return true;
        } catch (\Throwable $e) {
            $this->logger->error(sprintf(
                'ETL %s FAILED [%s]: %s',
                $indicatorId,
                $shortName,
                $e->getMessage()
            ), ['exception' => $e]);

            return false;
        } finally {
            $this->currentContext = null;
        }
    }

    // === Abstract contract — implement in subclasses ===

    /**
     * Return all indicator definitions this importer handles, keyed by indicator ID.
     *
     * @return array<string, IndicatorDefinition>
     */
    abstract protected function getDefinitions(): array;

    /**
     * Perform the actual fetch → parse → persist for one indicator.
     * Target + Indicator rows are guaranteed to exist ($indicator is passed in).
     * Use setMunicipalityValue / setComarcaValue / setProvinceValue to write rows.
     * The flush is handled by run().
     */
    abstract protected function import(
        IndicatorDefinition $def,
        Indicator $indicator,
        EtlContext $context,
    ): void;

    // === Scope-aware write helpers ===

    protected function setMunicipalityValue(
        IndicatorDefinition $def,
        Indicator $indicator,
        Municipality $mun,
        int $year,
        float $value,
        ?float $value2 = null,
        ?int $subindicator = null,
    ): void {
        if (!$this->shouldImport(ImportScope::Municipality)) {
            return;
        }
        $result = $this->values->setMunicipalityValue($year, $indicator, $mun, $value, $value2, $def->unit, $subindicator);
        $this->track($result);
    }

    protected function setComarcaValue(
        IndicatorDefinition $def,
        Indicator $indicator,
        Comarca $com,
        int $year,
        float $value,
        ?float $value2 = null,
        ?int $subindicator = null,
    ): void {
        if (!$this->shouldImport(ImportScope::Comarca)) {
            return;
        }
        $result = $this->values->setComarcaValue($year, $indicator, $com, $value, $value2, $subindicator);
        $this->track($result);
    }

    protected function setProvinceValue(
        IndicatorDefinition $def,
        Indicator $indicator,
        Province $prov,
        int $year,
        float $value,
        ?float $value2 = null,
        ?int $subindicator = null,
    ): void {
        if (!$this->shouldImport(ImportScope::Province)) {
            return;
        }
        $result = $this->values->setProvinceValue($year, $indicator, $prov, $value, $value2, $subindicator);
        $this->track($result);
    }

    protected function track(ValueWriteResult $result): void
    {
        match ($result) {
            ValueWriteResult::Created   => ++$this->created,
            ValueWriteResult::Updated   => ++$this->updated,
            ValueWriteResult::Unchanged => ++$this->unchanged,
            ValueWriteResult::Skipped   => ++$this->skipped,
        };
    }

    // === Municipality lookup ===

    /** Convenience wrapper around GeoRegistry — most importers reach for this. */
    protected function getMunicipalityByCode(string $rawCode): ?Municipality
    {
        return $this->geo->getMunicipalityByCode($rawCode);
    }

    // === Scope filter ===

    /**
     * True if the current run includes the given scope.
     *
     * Use this to gate expensive operations (HTTP fetches, file reads) when
     * their scope is excluded. The single-row write helpers above also call
     * this internally, so forgetting the orchestration-level check is wasteful
     * but never incorrect.
     */
    protected function shouldImport(ImportScope $scope): bool
    {
        return null === $this->currentContext || $this->currentContext->hasScope($scope);
    }

    // === Aggregation hook ===

    /**
     * After municipality (and any directly-imported comarca/province) rows are flushed,
     * the appropriate `AggregationCalculatorService::calculateFor*` methods run for each
     * scope that's enabled in the context and that classifies the indicator in
     * AggregationConfig. Each call picks the right strategy (Ratio / PopulationWeighted /
     * Average / BeachesWeighted) and overwrites the indicator+group rows in the value
     * tables. Set $context->skipAggregation = true to bypass entirely.
     */
    protected function afterSuccess(IndicatorDefinition $def, EtlContext $context): void
    {
        if ($context->skipAggregation) {
            $this->logger->debug(sprintf('ETL %s aggregation skipped (skipAggregation=true).', $def->indicatorId));

            return;
        }

        $id = $def->indicatorId;

        if ($context->hasScope(ImportScope::Comarca)
            && in_array($id, AggregationConfig::getAllEligibleComarcaIndicators(), true)) {
            $this->logger->info(sprintf('ETL %s → aggregating comarca…', $id));
            $this->aggregations->calculateForComarca($id);
        }

        if ($context->hasScope(ImportScope::Province)
            && in_array($id, AggregationConfig::getAllEligibleProvinceIndicators(), true)) {
            $this->logger->info(sprintf('ETL %s → aggregating province…', $id));
            $this->aggregations->calculateForProvince($id);
        }

        if ($context->hasScope(ImportScope::Aggregation)
            && in_array($id, AggregationConfig::getAllEligibleIndicators(), true)) {
            $this->logger->info(sprintf('ETL %s → aggregating aggregations…', $id));
            $this->aggregations->calculateForAggregation($id);
        }
    }

    // === Internal ===

    private function resetCounters(): void
    {
        $this->created = $this->updated = $this->unchanged = $this->skipped = 0;
    }
}
