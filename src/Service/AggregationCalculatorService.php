<?php

namespace App\Service;

use App\Config\AggregationConfig;
use App\Entity\Aggregation;
use App\Entity\AggregationValue;
use App\Entity\Comarca;
use App\Entity\ComarcaValue;
use App\Entity\Indicator;
use App\Entity\Province;
use App\Entity\ProvinceValue;
use App\Repository\AggregationRepository;
use App\Repository\AggregationValueRepository;
use App\Repository\ComarcaRepository;
use App\Repository\ComarcaValueRepository;
use App\Repository\IndicatorRepository;
use App\Repository\ProvinceRepository;
use App\Repository\ProvinceValueRepository;
use App\Service\Aggregation\AggregationStrategyInterface;
use App\Service\Aggregation\GroupContext;
use Doctrine\ORM\EntityManagerInterface;

class AggregationCalculatorService
{
    /** @var AggregationStrategyInterface[] */
    private array $strategies;

    /**
     * @param iterable<AggregationStrategyInterface> $strategies Tagged with app.aggregation_strategy
     */
    public function __construct(
        iterable $strategies,
        private readonly EntityManagerInterface $entityManager,
        private readonly AggregationValueRepository $aggregationValueRepository,
        private readonly AggregationRepository $aggregationRepository,
        private readonly ComarcaRepository $comarcaRepository,
        private readonly ComarcaValueRepository $comarcaValueRepository,
        private readonly ProvinceRepository $provinceRepository,
        private readonly ProvinceValueRepository $provinceValueRepository,
        private readonly IndicatorRepository $indicatorRepository,
    ) {
        $this->strategies = $strategies instanceof \Traversable
            ? iterator_to_array($strategies)
            : (array) $strategies;
    }

    /**
     * Calculate and persist AggregationValue records for all aggregations (or one by slug).
     */
    public function calculateForAggregation(string $indicatorId, ?string $aggregationSlug = null): void
    {
        if (!in_array($indicatorId, AggregationConfig::getAllEligibleIndicators(), true)) {
            throw new \InvalidArgumentException("Indicator \"$indicatorId\" is not eligible for aggregation calculation.");
        }

        $indicator = $this->loadIndicator($indicatorId);

        if ($aggregationSlug !== null) {
            $aggregation = $this->aggregationRepository->findOneBy(['slug' => $aggregationSlug]);
            if (!$aggregation) {
                throw new \InvalidArgumentException("Aggregation not found: $aggregationSlug");
            }
            $this->calculateForGroup($indicator, $this->buildAggregationContext($aggregation));
        } else {
            foreach ($this->aggregationRepository->findAll() as $aggregation) {
                $this->calculateForGroup($indicator, $this->buildAggregationContext($aggregation));
            }
        }
    }

    /**
     * Calculate and persist ComarcaValue records for all comarcas (or one by comarca_code).
     */
    public function calculateForComarca(string $indicatorId, ?string $comarcaCode = null): void
    {
        if (!in_array($indicatorId, AggregationConfig::getAllEligibleComarcaIndicators(), true)) {
            throw new \InvalidArgumentException("Indicator \"$indicatorId\" is not eligible for comarca calculation.");
        }

        $indicator = $this->loadIndicator($indicatorId);

        if ($comarcaCode !== null) {
            $comarca = $this->comarcaRepository->findOneBy(['comarca_code' => $comarcaCode]);
            if (!$comarca) {
                throw new \InvalidArgumentException("Comarca not found: $comarcaCode");
            }
            $this->calculateForGroup($indicator, $this->buildComarcaContext($comarca));
        } else {
            foreach ($this->comarcaRepository->findAll() as $comarca) {
                $this->calculateForGroup($indicator, $this->buildComarcaContext($comarca));
            }
        }
    }

    /**
     * Calculate and persist ProvinceValue records for all provinces (or one by province_code).
     */
    public function calculateForProvince(string $indicatorId, ?string $provinceCode = null): void
    {
        if (!in_array($indicatorId, AggregationConfig::getAllEligibleProvinceIndicators(), true)) {
            throw new \InvalidArgumentException("Indicator \"$indicatorId\" is not eligible for province calculation.");
        }

        $indicator = $this->loadIndicator($indicatorId);

        if ($provinceCode !== null) {
            $province = $this->provinceRepository->findOneBy(['province_code' => $provinceCode]);
            if (!$province) {
                throw new \InvalidArgumentException("Province not found: $provinceCode");
            }
            $this->calculateForGroup($indicator, $this->buildProvinceContext($province));
        } else {
            foreach ($this->provinceRepository->findAll() as $province) {
                $this->calculateForGroup($indicator, $this->buildProvinceContext($province));
            }
        }
    }

    private function calculateForGroup(Indicator $indicator, GroupContext $group): void
    {
        $strategy = $this->findStrategy($indicator);

        if ($strategy === null) {
            return;
        }

        $rows = $strategy->calculate($indicator, $group);

        $this->deleteExistingValues($indicator, $group);

        foreach ($rows as $row) {
            $this->upsertValue($indicator, $group, (int) $row['year'], (float) $row['value'], isset($row['value2']) ? (float) $row['value2'] : null);
        }

        $this->entityManager->flush();
    }

    private function deleteExistingValues(Indicator $indicator, GroupContext $group): void
    {
        if ($group->type === 'aggregation') {
            $this->entityManager->createQuery(
                'DELETE FROM App\Entity\AggregationValue av
                 WHERE av.indicator = :indicator AND av.aggregation = :groupId'
            )
                ->setParameter('indicator', $indicator)
                ->setParameter('groupId', $group->id)
                ->execute();
        } elseif ($group->type === 'comarca') {
            $this->entityManager->createQuery(
                'DELETE FROM App\Entity\ComarcaValue cv
                 WHERE cv.indicator = :indicator AND cv.comarca = :groupId'
            )
                ->setParameter('indicator', $indicator)
                ->setParameter('groupId', $group->id)
                ->execute();
        } elseif ($group->type === 'province') {
            $this->entityManager->createQuery(
                'DELETE FROM App\Entity\ProvinceValue pv
                 WHERE pv.indicator = :indicator AND pv.province = :groupId'
            )
                ->setParameter('indicator', $indicator)
                ->setParameter('groupId', $group->id)
                ->execute();
        }
    }

    private function upsertValue(Indicator $indicator, GroupContext $group, int $year, float $value, ?float $value2 = null): void
    {
        if ($group->type === 'aggregation') {
            $aggregation = $this->aggregationRepository->find($group->id);

            $record = $this->aggregationValueRepository->findOneBy([
                'aggregation' => $aggregation,
                'indicator'   => $indicator,
                'year'        => $year,
            ]);

            if (!$record) {
                $record = new AggregationValue();
                $record->setAggregation($aggregation);
                $record->setIndicator($indicator);
                $record->setYear($year);
                $record->setUnit($indicator->getUnit() ?? '');
                $this->entityManager->persist($record);
            }

            $record->setValue($value);
            $record->setValue2($value2);

        } elseif ($group->type === 'comarca') {
            $comarca = $this->comarcaRepository->find($group->id);

            $record = $this->comarcaValueRepository->findOneBy([
                'comarca'   => $comarca,
                'indicator' => $indicator,
                'year'      => $year,
            ]);

            if (!$record) {
                $record = new ComarcaValue();
                $record->setComarca($comarca);
                $record->setIndicator($indicator);
                $record->setYear($year);
                // $record->setUnit($indicator->getUnit() ?? '');
                $this->entityManager->persist($record);
            }

            $record->setValue($value);
            $record->setValue2($value2);

        } elseif ($group->type === 'province') {
            $province = $this->provinceRepository->find($group->id);

            $record = $this->provinceValueRepository->findOneBy([
                'province'  => $province,
                'indicator' => $indicator,
                'year'      => $year,
            ]);

            if (!$record) {
                $record = new ProvinceValue();
                $record->setProvince($province);
                $record->setIndicator($indicator);
                $record->setYear($year);
                $this->entityManager->persist($record);
            }

            $record->setValue($value);
            $record->setValue2($value2);
        }
    }

    private function findStrategy(Indicator $indicator): ?AggregationStrategyInterface
    {
        foreach ($this->strategies as $strategy) {
            if ($strategy->supports($indicator)) {
                return $strategy;
            }
        }

        return null;
    }

    private function loadIndicator(string $indicatorId): Indicator
    {
        $indicator = $this->indicatorRepository->findOneBy(['indicator_id' => $indicatorId]);

        if (!$indicator) {
            throw new \InvalidArgumentException("Indicator not found: $indicatorId");
        }

        return $indicator;
    }

    private function buildAggregationContext(Aggregation $aggregation): GroupContext
    {
        return new GroupContext(
            id: $aggregation->getId(),
            type: 'aggregation',
            memberJoinSql: 'JOIN municipality_aggregation _grp ON _grp.municipality_id = mv.municipality_id AND _grp.aggregation_id = :groupId',
        );
    }

    private function buildComarcaContext(Comarca $comarca): GroupContext
    {
        return new GroupContext(
            id: $comarca->getId(),
            type: 'comarca',
            memberJoinSql: 'JOIN municipality _grp ON mv.municipality_id = _grp.id AND _grp.comarca_id = :groupId',
        );
    }

    private function buildProvinceContext(Province $province): GroupContext
    {
        return new GroupContext(
            id: $province->getId(),
            type: 'province',
            memberJoinSql: 'JOIN municipality _grp ON mv.municipality_id = _grp.id JOIN comarca _c ON _grp.comarca_id = _c.id AND _c.province_id = :groupId',
        );
    }
}
