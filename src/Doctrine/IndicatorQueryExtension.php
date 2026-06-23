<?php

namespace App\Doctrine;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryResultCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\Indicator;
use App\Entity\Target;
// use App\Entity\MunicipalityValue;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

class IndicatorQueryExtension implements QueryCollectionExtensionInterface, QueryResultCollectionExtensionInterface
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function applyToCollection(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operationName = null,
        array $context = [],
    ): void {
        // We only apply this extension to the Indicator entity
        // and also targets && Target::class !== $resourceClass
        if (Indicator::class !== $resourceClass) {
            return;
        }

        $rootAlias = $queryBuilder->getRootAliases()[0];

        if (Indicator::class === $resourceClass) {
            // Apply logic when directly fetching indicators
            $queryBuilder->leftJoin("$rootAlias.municipalityValues", 'mv')
                ->addSelect('COUNT(DISTINCT mv.municipality) AS HIDDEN municipalityCount')
                ->addSelect('COUNT(DISTINCT mv.year) AS HIDDEN yearCount')
                ->addSelect('MAX(mv.updatedAt) AS HIDDEN mostRecentDate')
                ->addSelect('MAX(mv.year) AS HIDDEN lastYearAvailable')
                // ->addSelect('GROUP_CONCAT(DISTINCT mv.year ORDER BY mv.year DESC) AS HIDDEN allAvailableYears') // Add all available years
                ->groupBy("$rootAlias.id");
        } elseif (Target::class === $resourceClass) {
            // Apply logic when retrieving targets, ensuring related indicators are modified
            $queryBuilder->leftJoin("$rootAlias.indicators", 'indicator')
                ->leftJoin('indicator.municipalityValues', 'mv')
                ->addSelect('COUNT(DISTINCT mv.municipality) AS HIDDEN municipalityCount')
                ->addSelect('COUNT(DISTINCT mv.year) AS HIDDEN yearCount')
                ->addSelect('MAX(mv.updatedAt) AS HIDDEN mostRecentDate')
                ->addSelect('MAX(mv.year) AS HIDDEN lastYearAvailable')
                ->groupBy('indicator.id', "$rootAlias.id"); // Include all necessary columns from target

            // show the complete query
            // dd($queryBuilder->getQuery()->getSQL());
        }
    }

    public function applyToItem(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        array $identifiers,
        ?string $operationName = null,
        array $context = [],
    ): void {
        // No special logic for individual items
    }

    public function supportsResult(string $resourceClass, ?Operation $operationName = null, array $context = []): bool
    {
        return Indicator::class === $resourceClass;
    }

    public function getResult(
        QueryBuilder $queryBuilder,
        ?string $resourceClass = null,
        ?Operation $operationName = null,
        array $context = []): iterable
    {
        $indicators = $queryBuilder->getQuery()->getResult();
        // dd($queryBuilder->getQuery()->getSQL());
        foreach ($indicators as $indicator) {
            // dd($indicator);

            $result = $this->entityManager->createQueryBuilder()
                ->select(
                    'COUNT(DISTINCT mv.municipality) AS municipalityCount',
                    'COUNT(DISTINCT mv.year) AS yearCount',
                    'MAX(mv.updatedAt) AS mostRecentDate',
                    'MAX(mv.year) AS lastYearAvailable'
                )
                ->from('App\Entity\MunicipalityValue', 'mv')
                ->where('mv.indicator = :indicatorId')
                ->setParameter('indicatorId', $indicator->getId())
                ->getQuery()
                ->getSingleResult();

            $indicator->setMunicipalityCount((int) $result['municipalityCount']);
            $indicator->setYearCount((int) $result['yearCount']);
            $indicator->setLastYearAvailable((int) $result['lastYearAvailable']);
            // Convert the date string to DateTime object if not null
            $mostRecentDate = $result['mostRecentDate']
            ? new \DateTime($result['mostRecentDate'])
            : null;

            // Process the list of all available years
            // $years = explode(',', $result['allAvailableYears']);
            // $result->years(array_map('intval', $years)); // Store as an array of integers

            $indicator->setMostRecentDate($mostRecentDate);
        }

        return $indicators;

        // $results = $queryBuilder->getQuery()->getResult();

        // foreach ($results as $result) {
        //     if ($result instanceof Indicator) {
        //         dd($result);
        //         // Manually set the aggregated fields in the Indicator entity
        //         $result->setMunicipalityCount((int) $result['municipalityCount']);
        //         $result->setYearCount((int) $result['yearCount']);
        //         $result->setMostRecentDate($result['mostRecentDate'] ? new \DateTime($result['mostRecentDate']) : null);
        //     } elseif ($result instanceof Target) {
        //         // Iterate over indicators and set the aggregated values
        //         foreach ($result->getIndicators() as $indicator) {
        //             $indicator->setMunicipalityCount((int) $indicator['municipalityCount']);
        //             $indicator->setYearCount((int) $indicator['yearCount']);
        //             $indicator->setMostRecentDate($indicator['mostRecentDate'] ? new \DateTime($indicator['mostRecentDate']) : null);
        //         }
        //     }
        // }

        // return $results;
    }

    /*
     * This method is called after the query is executed.
     */
    // public function postProcess(iterable $results): iterable
    // {
    //     foreach ($results as $result) {
    //         // Check if result is an Indicator object (or a Target object containing Indicators)
    //         if ($result instanceof Indicator) {
    //             // Assuming that custom fields were added as HIDDEN values (not part of the original Indicator entity)
    //             // You must manually set these fields if the result is a direct Indicator object
    //             $result->setMunicipalityCount((int) $result['municipalityCount']);
    //             $result->setYearCount((int) $result['yearCount']);
    //             $result->setMostRecentDate($result['mostRecentDate'] ? new \DateTime($result['mostRecentDate']) : null);
    //         } elseif ($result instanceof Target) {
    //             // If result is a Target, iterate over its indicators and set the custom fields
    //             foreach ($result->getIndicators() as $indicator) {
    //                 $indicator->setMunicipalityCount((int) $indicator['municipalityCount']);
    //                 $indicator->setYearCount((int) $indicator['yearCount']);
    //                 $indicator->setMostRecentDate($indicator['mostRecentDate'] ? new \DateTime($indicator['mostRecentDate']) : null);
    //             }
    //         }
    //     }

    //     return $results;
    // }
}
