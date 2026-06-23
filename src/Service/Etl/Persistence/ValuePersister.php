<?php

namespace App\Service\Etl\Persistence;

use App\Entity\Comarca;
use App\Entity\ComarcaValue;
use App\Entity\Indicator;
use App\Entity\Municipality;
use App\Entity\MunicipalityValue;
use App\Entity\Province;
use App\Entity\ProvinceValue;
use App\Service\Etl\Util\EtlUtils;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Upserts MunicipalityValue / ComarcaValue / ProvinceValue rows.
 * Persists entities but does not flush — caller controls the transaction.
 *
 * Each set*Value returns a ValueWriteResult so the caller can track Created /
 * Updated / Unchanged / Skipped counters.
 */
class ValuePersister
{
    /** Float-equality tolerance for the Created vs. Updated vs. Unchanged decision. */
    private const FLOAT_EPSILON = 1e-9;

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function setMunicipalityValue(
        int $year,
        Indicator $indicator,
        ?Municipality $municipality,
        float|int|string|null $value,
        float|int|string|null $value2 = null,
        ?string $unit = null,
        ?int $subindicator = null,
    ): ValueWriteResult {
        if (!$municipality) {
            return ValueWriteResult::Skipped;
        }

        $entry = $this->entityManager->getRepository(MunicipalityValue::class)->findOneBy([
            'year' => $year,
            'indicator' => $indicator,
            'municipality' => $municipality,
            'subindicator' => $subindicator,
        ]);

        $isNew = null === $entry;
        $oldValue  = $isNew ? null : $entry->getValue();
        $oldValue2 = $isNew ? null : $entry->getValue2();
        $oldUnit   = $isNew ? null : $entry->getUnit();

        if ($isNew) {
            $entry = new MunicipalityValue();
            $entry->setYear($year);
            $entry->setMunicipality($municipality);
            $entry->setIndicator($indicator);
            $entry->setSubindicator($subindicator);
        }

        $newValue  = EtlUtils::toFloat($value);
        $newValue2 = null === $value2 ? null : EtlUtils::toFloat($value2);

        $entry->setValue($newValue);
        $entry->setValue2($newValue2);
        $entry->setUnit($unit);

        $this->entityManager->persist($entry);

        if ($isNew) {
            return ValueWriteResult::Created;
        }
        return $this->floatsEqual($oldValue, $newValue) && $this->floatsEqual($oldValue2, $newValue2) && $oldUnit === $unit
            ? ValueWriteResult::Unchanged
            : ValueWriteResult::Updated;
    }

    public function setComarcaValue(
        int $year,
        Indicator $indicator,
        ?Comarca $comarca,
        float|int|string|null $value,
        float|int|string|null $value2 = null,
        ?int $subindicator = null,
    ): ValueWriteResult {
        if (!$comarca) {
            return ValueWriteResult::Skipped;
        }

        $entry = $this->entityManager->getRepository(ComarcaValue::class)->findOneBy([
            'year' => $year,
            'indicator' => $indicator,
            'comarca' => $comarca,
            'subindicator' => $subindicator,
        ]);

        $isNew = null === $entry;
        $oldValue  = $isNew ? null : $entry->getValue();
        $oldValue2 = $isNew ? null : $entry->getValue2();

        if ($isNew) {
            $entry = new ComarcaValue();
            $entry->setYear($year);
            $entry->setComarca($comarca);
            $entry->setIndicator($indicator);
            $entry->setSubindicator($subindicator);
        }

        $newValue  = EtlUtils::toFloat($value);
        $newValue2 = null === $value2 ? null : EtlUtils::toFloat($value2);

        $entry->setValue($newValue);
        $entry->setValue2($newValue2);

        $this->entityManager->persist($entry);

        if ($isNew) {
            return ValueWriteResult::Created;
        }
        return $this->floatsEqual($oldValue, $newValue) && $this->floatsEqual($oldValue2, $newValue2)
            ? ValueWriteResult::Unchanged
            : ValueWriteResult::Updated;
    }

    public function setProvinceValue(
        int $year,
        Indicator $indicator,
        ?Province $province,
        float|int|string|null $value,
        float|int|string|null $value2 = null,
        ?int $subindicator = null,
    ): ValueWriteResult {
        if (!$province) {
            return ValueWriteResult::Skipped;
        }

        $entry = $this->entityManager->getRepository(ProvinceValue::class)->findOneBy([
            'year' => $year,
            'indicator' => $indicator,
            'province' => $province,
            'subindicator' => $subindicator,
        ]);

        $isNew = null === $entry;
        $oldValue  = $isNew ? null : $entry->getValue();
        $oldValue2 = $isNew ? null : $entry->getValue2();

        if ($isNew) {
            $entry = new ProvinceValue();
            $entry->setYear($year);
            $entry->setProvince($province);
            $entry->setIndicator($indicator);
            $entry->setSubindicator($subindicator);
        }

        $newValue  = EtlUtils::toFloat($value);
        $newValue2 = null === $value2 ? null : EtlUtils::toFloat($value2);

        $entry->setValue($newValue);
        $entry->setValue2($newValue2);

        $this->entityManager->persist($entry);

        if ($isNew) {
            return ValueWriteResult::Created;
        }
        return $this->floatsEqual($oldValue, $newValue) && $this->floatsEqual($oldValue2, $newValue2)
            ? ValueWriteResult::Unchanged
            : ValueWriteResult::Updated;
    }

    /** Treats two floats as equal within FLOAT_EPSILON; nulls compare equal to nulls only. */
    private function floatsEqual(?float $a, ?float $b): bool
    {
        if (null === $a || null === $b) {
            return null === $a && null === $b;
        }
        return abs($a - $b) < self::FLOAT_EPSILON;
    }
}
