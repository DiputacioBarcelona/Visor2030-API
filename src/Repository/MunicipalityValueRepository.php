<?php

namespace App\Repository;

use App\Entity\Indicator;
use App\Entity\Municipality;
use App\Entity\MunicipalityValue;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MunicipalityValue>
 */
class MunicipalityValueRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MunicipalityValue::class);
    }

    public function findLatestValuesByComarca(string $comarcaCode)
    {
        $qb = $this->createQueryBuilder('v');

        $qb->innerJoin('v.municipality', 'm')
            ->innerJoin('m.comarca', 'c')
            ->andWhere('c.comarcaCode = :comarcaCode')
            ->setParameter('comarcaCode', $comarcaCode)
            ->andWhere(
                $qb->expr()->eq(
                    'v.year',
                    $this->createQueryBuilder('v2')
                        ->select('MAX(v2.year)')
                        ->where('v2.municipality = v.municipality')
                        ->getDQL()
                )
            );

        return $qb->getQuery()->getResult();
    }

    public function findBySdg(string $sdg, ?string $municipality = null, ?string $year = null): array
    {
        // return $this->createQueryBuilder('mv')
        //     ->join('mv.indicator', 'i')
        //     ->join('i.target', 't')
        //     ->where('t.sdg = :sdg')
        //     ->setParameter('sdg', $sdg)
        //     ->getQuery()
        //     ->getResult();

        // municipality is optional
        $qb = $this->createQueryBuilder('mv')
            ->join('mv.indicator', 'i')
            ->join('i.target', 't')
            ->where('t.sdg = :sdg')
            ->setParameter('sdg', $sdg);

        if ($municipality) {
            // join the municipality table
            $qb->join('mv.municipality', 'm')
                ->andWhere('m.municipality_code = :municipality')
                ->setParameter('municipality', $municipality);
        }

        if ($year) {
            // filter by year
            $qb->andWhere('mv.year = :year')
                ->setParameter('year', $year);
        }

        return $qb->getQuery()->getResult();
    }

    public function findByIndicator(string $indicator, ?string $municipality = null, ?string $year = null): array
    {
        // return $this->createQueryBuilder('mv')
        //     ->join('mv.indicator', 'i')
        //     ->join('i.target', 't')
        //     ->where('i.indicator_id = :indicator')
        //     ->setParameter('indicator', $indicator)
        //     ->getQuery()
        //     ->getResult();

        // municipality is optional
        $qb = $this->createQueryBuilder('mv')
            ->join('mv.indicator', 'i')
            ->where('i.indicator_id = :indicator')
            ->setParameter('indicator', $indicator);

        if ($municipality) {
            // join the municipality table
            $qb->join('mv.municipality', 'm')
                ->andWhere('m.municipality_code = :municipality')
                ->setParameter('municipality', $municipality);
        }

        if ($year) {
            // filter by year
            $qb->andWhere('mv.year = :year')
                ->setParameter('year', $year);
        }

        return $qb->getQuery()->getResult();
    }

    public function findClosestPreviousYear(int $year, Municipality $municipality, Indicator $indicator): ?MunicipalityValue
    {
        return $this->createQueryBuilder('mv')
            ->andWhere('mv.municipality = :municipality')
            ->andWhere('mv.indicator = :indicator')
            ->andWhere('mv.year < :year')
            ->setParameter('municipality', $municipality)
            ->setParameter('indicator', $indicator)
            ->setParameter('year', $year)
            ->orderBy('mv.year', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Municipalities that have no MunicipalityValue row for the given (year, indicator).
     * Used by importers that backfill gaps with the previous year's value.
     *
     * @return Municipality[]
     */
    public function findMunicipalitiesWithoutValue(int $year, Indicator $indicator): array
    {
        $em = $this->getEntityManager();

        $allCodes = array_map(
            fn (Municipality $m) => $m->getMunicipalityCode6(),
            $em->getRepository(Municipality::class)->findAll()
        );

        $presentCodes = array_map(
            fn (MunicipalityValue $mv) => $mv->getMunicipality()->getMunicipalityCode6(),
            $this->findBy(['year' => $year, 'indicator' => $indicator])
        );

        $missing = array_diff($allCodes, $presentCodes);
        if (empty($missing)) {
            return [];
        }

        return $em->getRepository(Municipality::class)->findBy(['municipality_code_6' => $missing]);
    }

    public function getIndicatorYears(Indicator $indicator): array
    {
        $years = $this->createQueryBuilder('mv')
            ->select('mv.year')
            ->andWhere('mv.indicator = :indicator')
            ->setParameter('indicator', $indicator)
            ->groupBy('mv.year')
            ->orderBy('mv.year', 'ASC')
            ->getQuery()
            ->getArrayResult();

        return array_map(function ($year) {
            return $year['year'];
        }, $years);
    }

    //    /**
    //     * @return MunicipalityValue[] Returns an array of MunicipalityValue objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('m')
    //            ->andWhere('m.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('m.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?MunicipalityValue
    //    {
    //        return $this->createQueryBuilder('m')
    //            ->andWhere('m.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    // public function getSumByComarcaAndYear()
    // {
    //     return $this->createQueryBuilder('mv')
    //         ->select('IDENTITY(m.comarca) AS comarca_id', 'mv.year', 'SUM(mv.value) AS total_value', 'SUM(mv.value2) AS total_value2')
    //         ->join('mv.municipality', 'm')
    //         ->groupBy('comarca_id', 'mv.year')
    //         ->getQuery()
    //         ->getResult();
    // }
}
