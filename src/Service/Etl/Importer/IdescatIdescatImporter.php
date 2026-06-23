<?php

namespace App\Service\Etl\Importer;

use App\Entity\Indicator;
use App\Service\Etl\Dto\EtlContext;
use App\Service\Etl\Dto\IndicatorDefinition;
use App\Service\Etl\Enum\ImportScope;
use App\Service\Etl\Util\EtlUtils;

/**
 * Migrates indicators that use IDESCAT affiliate data as numerator and IDESCAT
 * population-by-age data as denominator. Both municipality and comarca/province
 * values are imported directly from the API (no derivation).
 *
 * Indicators:
 *   5.c.2 — Taxa ocupació dones
 *            numerator : IDESCAT afi/8604/8704 female affiliates (annual average)
 *            denominator: IDESCAT censph female pop ages 16–64
 */
final class IdescatIdescatImporter extends AbstractEtlImporter
{
    // Female working-age population: 16–64 (same list used in legacy IDESCATIDESCATService)
    private const AGES_16_64 = 'Y016,Y017,Y018,Y019,Y020,Y021,Y022,Y023,Y024,Y025,Y026,Y027,Y028,Y029,Y030,Y031,Y032,Y033,Y034,Y035,Y036,Y037,Y038,Y039,Y040,Y041,Y042,Y043,Y044,Y045,Y046,Y047,Y048,Y049,Y050,Y051,Y052,Y053,Y054,Y055,Y056,Y057,Y058,Y059,Y060,Y061,Y062,Y063,Y064';

    private const MUN_FILTER = 'mun='.EtlUtils::BCN_MUNICIPALITY_FILTER;

    protected function getDefinitions(): array
    {
        return [
            '5.c.2' => new IndicatorDefinition(
                indicatorId: '5.c.2',
                targetId: '5.c',
                targetName: "Enfortir les polítiques i els plans de igualtat de gènere i d'empoderament de dones i nenes.",
                sdg: 5,
                indicatorName: 'Taxa ocupació dones',
                indicatorDescription: '',
                sign: true,
                source: 'IDESCAT',
                unit: 'percent',
                scale: 1,
                // urlInfo used to discover available years (MONTH-based API — getYearsIDESCAT returns last month per year)
                urlInfo: 'https://api.idescat.cat/taules/v2/afi/8604/8704/mun/?SEX=F&'.self::MUN_FILTER,
            ),
        ];
    }

    protected function import(IndicatorDefinition $def, Indicator $indicator, EtlContext $context): void
    {
        // getYearsIDESCAT on a MONTH-based info URL returns strings like '2024M12' (last month per year).
        // Truncate to 4-char year and deduplicate.
        $yearStrings = $this->idescatJson->getYears($def->urlInfo);
        $years = array_unique(array_map(fn ($ym) => substr((string) $ym, 0, 4), $yearStrings));

        foreach ($years as $year) {
            if ($this->shouldImport(ImportScope::Municipality)) {
                $affiliates = $this->idescatJson->getAffiliatesByYear($year, 'F', 'MUN');
                $populations = $this->idescatJson->getMunicipalityPopulationByAges($year, self::AGES_16_64, 'F');

                foreach ($affiliates as $munCode => $value) {
                    if (null === $value) {
                        continue;
                    }
                    $municipality = $this->geo->getMunicipalityByCode($munCode);
                    if (!$municipality) {
                        continue;
                    }
                    $value2 = $populations[$municipality->getMunicipalityCode6()] ?? null;
                    if (!$value2) {
                        continue;
                    }
                    $this->setMunicipalityValue($def, $indicator, $municipality, (int) $year, (float) $value, (float) $value2);
                }

                $this->em->flush();
            }

            if ($this->shouldImport(ImportScope::Comarca)) {
                $affiliates = $this->idescatJson->getAffiliatesByYear($year, 'F', 'COM');
                $populations = $this->idescatJson->getComarcaPopulationByAges($year, self::AGES_16_64, 'F');

                foreach ($affiliates as $comCode => $value) {
                    if (null === $value) {
                        continue;
                    }
                    $comarca = $this->geo->getComarcaByCode((string) $comCode);
                    if (!$comarca) {
                        continue;
                    }
                    $value2 = $populations[$comarca->getComarcaCode()] ?? null;
                    if (!$value2) {
                        continue;
                    }
                    $this->track($this->values->setComarcaValue((int) $year, $indicator, $comarca, (float) $value, (float) $value2));
                }

                $this->em->flush();
            }

            if ($this->shouldImport(ImportScope::Province)) {
                $affiliates = $this->idescatJson->getAffiliatesByYear($year, 'F', 'PROV');
                $populations = $this->idescatJson->getProvincePopulationByAges($year, self::AGES_16_64, 'F');

                foreach ($affiliates as $provCode => $value) {
                    if (null === $value) {
                        continue;
                    }
                    $province = $this->geo->getProvince();
                    if (!$province) {
                        continue;
                    }
                    $value2 = $populations[$province->getProvinceCode()] ?? null;
                    if (!$value2) {
                        continue;
                    }
                    $this->track($this->values->setProvinceValue((int) $year, $indicator, $province, (float) $value, (float) $value2));
                }

                $this->em->flush();
            }
        }
    }
}
