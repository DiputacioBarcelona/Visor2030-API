<?php

namespace App\Service\Etl\Importer;

use App\Entity\Indicator;
use App\Service\Etl\Dto\EtlContext;
use App\Service\Etl\Dto\IndicatorDefinition;
use App\Service\Etl\Enum\ImportScope;

/**
 * Handles indicators sourced from the Transparència Catalunya open-data API
 * using the DODO (DO-DO) source pattern.
 *
 * Import strategies (keyed by extra['import']):
 *   'simple'      — lookup by name or code; value + population; optionally fill missing with 0
 *   'accumulated' — 17.17.2: per-year count accumulated onto the previous year's total
 *   '4a1'         — 4.a.1: cross-reference students API + teachers API, aggregate by municipality
 *
 * Year discovery:
 *   default      — getYearsDO(urlInfo)
 *   currentYear  — [date('Y')] (static snapshot datasets: 1.5.1, 1.2.3, 12.2.1, 17.17.1, 11.4.2)
 *   sortAsc      — sorted oldest-first (accumulated: 17.17.2)
 *   minYear      — skip years before threshold (17.1.2: 2010)
 *   yearExtract4 — year field is a timestamp string; take first 4 chars (12.1.1, 17.17.2)
 */
final class DodoImporter extends AbstractEtlImporter
{
    protected function getDefinitions(): array
    {
        return [
            '3.6.1' => new IndicatorDefinition(
                indicatorId: '3.6.1',
                targetId: '3.6',
                targetName: 'Reduir a la meitat el nombre de morts i lesions causats per accidents de trànsit.',
                sdg: 3,
                indicatorName: 'Taxa mortalitat trànsit',
                indicatorDescription: 'Persones mortes o ferides greus en accidents de trànsit per cada 10.000 habitants',
                sign: true,
                source: 'DO_DO',
                unit: 'abs',
                scale: 10000,
                url: 'https://analisi.transparenciacatalunya.cat/resource/rmgc-ncpb.json?$query=SELECT%0A%20%20%60any%60%2C%0A%20%20%60nommun%60%2C%0A%20%20sum(%60f_morts%60)%20AS%20%60sum_f_morts%60%2C%0A%20%20sum(%60f_victimes%60)%20AS%20%60sum_f_victimes%60%2C%0A%20%20sum(%60f_ferits_greus%60)%20AS%20%60sum_f_ferits_greus%60%2C%0A%20%20sum(%60f_ferits_lleus%60)%20AS%20%60sum_f_ferits_lleus%60%0AWHERE%20caseless_one_of(%60nomdem%60%2C%20%22Barcelona%22)%0AGROUP%20BY%20%60any%60%2C%20%60nommun%60%0AHAVING%20%60any%60%20IN%20(%22[[[year]]]%22)',
                urlInfo: 'https://analisi.transparenciacatalunya.cat/resource/rmgc-ncpb.json?$query=SELECT%20%60any%60%20GROUP%20BY%20%60any%60%20ORDER%20BY%20%60any%60%20ASC%20NULL%20LAST',
                extra: ['import' => 'simple', 'munBy' => 'name', 'munField' => 'nommun', 'field1' => '__traffic_deaths', 'fillMissing' => true],
            ),
            '11.2.1' => new IndicatorDefinition(
                indicatorId: '11.2.1',
                targetId: '11.2',
                targetName: 'Proporcionar accés a sistemes de transport segurs, assequibles, accessibles, sostenibles i millorar la seguretat viària.',
                sdg: 11,
                indicatorName: 'Taxa mortalitat trànsit',
                indicatorDescription: 'Persones mortes o ferides greus en accidents de trànsit per cada 10.000 habitants',
                sign: true,
                source: 'DO_DO',
                unit: 'abs',
                scale: 10000,
                url: 'https://analisi.transparenciacatalunya.cat/resource/rmgc-ncpb.json?$query=SELECT%0A%20%20%60any%60%2C%0A%20%20%60nommun%60%2C%0A%20%20sum(%60f_morts%60)%20AS%20%60sum_f_morts%60%2C%0A%20%20sum(%60f_victimes%60)%20AS%20%60sum_f_victimes%60%2C%0A%20%20sum(%60f_ferits_greus%60)%20AS%20%60sum_f_ferits_greus%60%2C%0A%20%20sum(%60f_ferits_lleus%60)%20AS%20%60sum_f_ferits_lleus%60%0AWHERE%20caseless_one_of(%60nomdem%60%2C%20%22Barcelona%22)%0AGROUP%20BY%20%60any%60%2C%20%60nommun%60%0AHAVING%20%60any%60%20IN%20(%22[[[year]]]%22)',
                urlInfo: 'https://analisi.transparenciacatalunya.cat/resource/rmgc-ncpb.json?$query=SELECT%20%60any%60%20GROUP%20BY%20%60any%60%20ORDER%20BY%20%60any%60%20ASC%20NULL%20LAST',
                extra: ['import' => 'simple', 'munBy' => 'name', 'munField' => 'nommun', 'field1' => '__traffic_deaths', 'fillMissing' => true],
            ),
            '12.1.1' => new IndicatorDefinition(
                indicatorId: '12.1.1',
                targetId: '12.1',
                targetName: 'Fomentar el consum i producció sostenibles.',
                sdg: 12,
                indicatorName: "Instalacions d'autoconsum energia",
                indicatorDescription: "Instalacions d'autoconsum energia",
                sign: true,
                source: 'DO_DO',
                unit: 'abs',
                scale: 1,
                url: 'https://analisi.transparenciacatalunya.cat/resource/2b4s-skfm.json?$query=SELECT%0A%20%20date_trunc_y(%60data_de_posada_en_servei%60)%20AS%20%60by_year_data_de_posada_en_servei%60%2C%0A%20%20%60codi_ine_municipi%60%2C%0A%20%20count(*)%20AS%20%60count%60%0AWHERE%20caseless_starts_with(%60codi_ine_municipi%60%2C%20%2208%22)%0AGROUP%20BY%20date_trunc_y(%60data_de_posada_en_servei%60)%2C%20%60codi_ine_municipi%60%0AHAVING%0A%20%20%60by_year_data_de_posada_en_servei%60%0A%20%20%20%20%3D%20%22[[[year]]]%22%20%3A%3A%20floating_timestamp%0AORDER%20BY%20%60codi_ine_municipi%60%20ASC%20NULL%20LAST',
                urlInfo: 'https://analisi.transparenciacatalunya.cat/resource/2b4s-skfm.json?$query=SELECT%0A%20%20date_trunc_y(%60data_de_posada_en_servei%60)%20AS%20%60by_year_data_de_posada_en_servei%60%0AWHERE%20caseless_starts_with(%60codi_ine_municipi%60%2C%20%2208%22)%0AGROUP%20BY%20date_trunc_y(%60data_de_posada_en_servei%60)',
                extra: ['import' => 'simple', 'munBy' => 'code', 'munField' => 'codi_ine_municipi', 'field1' => 'count', 'yearKey' => 'by_year_data_de_posada_en_servei', 'yearExtract4' => true, 'fillMissing' => true, 'fillMissingMinYear' => 2012],
            ),
            '17.17.1' => new IndicatorDefinition(
                indicatorId: '17.17.1',
                targetId: '17.17',
                targetName: "Promoure la constitució d'aliances eficaces en els àmbits públic, publicoprivat i de la societat civil, aprofitant les estratègies d'obtenció de recursos dels partenariats.",
                sdg: 17,
                indicatorName: 'Nombre entitats ONGD dividit per població',
                indicatorDescription: 'Nombre entitats ONGD dividit per població',
                sign: true,
                source: 'DO_DO',
                unit: 'abs',
                scale: 1,
                url: 'https://analisi.transparenciacatalunya.cat/resource/4d69-gxhb.json?$query=SELECT%20%60municipi%60%2C%20count(%60n_mero_registre%60)%20AS%20%60count_n_mero_registre%60%0AGROUP%20BY%20%60municipi%60',
                extra: ['import' => 'simple', 'munBy' => 'name', 'munField' => 'municipi', 'field1' => 'count_n_mero_registre', 'currentYear' => true, 'fillMissing' => true],
            ),
            '1.5.1' => new IndicatorDefinition(
                indicatorId: '1.5.1',
                targetId: '1.5',
                targetName: 'Fomentar la resiliència de les persones pobres i vulnerables, i reduir la seva exposició i vulnerabilitat als fenòmens extrems relacionats amb el clima i altres pertorbacions i desastres econòmics, socials i ambientals.',
                sdg: 1,
                indicatorName: 'Refugis climàtics gratuïts',
                indicatorDescription: 'Refugis climàtics gratuïts',
                sign: true,
                source: 'DO_DO',
                unit: '',
                scale: 1,
                url: 'https://analisi.transparenciacatalunya.cat/resource/9gu7-iwci.json?$query=SELECT%0A%20%20%60codi_municipi%60%2C%0A%20%20count(%60refugi_clim_tic%60)%20AS%20%60count_refugi_clim_tic%60%0AWHERE%0A%20%20caseless_one_of(%60refugi_clim_tic%60%2C%20%22S%C3%AD%22)%0A%20%20AND%20caseless_one_of(%60gratu_tat%60%2C%20%22S%C3%AD%22)%0AGROUP%20BY%20%60codi_municipi%60%0AHAVING%20caseless_starts_with(%60codi_municipi%60%2C%20%2208%22)',
                extra: ['import' => 'simple', 'munBy' => 'code', 'munField' => 'codi_municipi', 'field1' => 'count_refugi_clim_tic', 'currentYear' => true, 'fillMissing' => true, 'skipShortCode' => true],
            ),
            '4.a.1' => new IndicatorDefinition(
                indicatorId: '4.a.1',
                targetId: '4.a',
                targetName: "Garantir que els equipaments educatius responguin a les necessitats dels infants i les persones amb discapacitat i tinguin en compte les qüestions de gènere, oferint entorns d'aprenentatge segurs, no violents, inclusius i eficaços.",
                sdg: 4,
                indicatorName: "Taxa d'alumnes per professor",
                indicatorDescription: "Taxa d'alumnes per professor",
                sign: true,
                source: 'DO_DO',
                unit: 'percent',
                scale: 1,
                url: 'https://analisi.transparenciacatalunya.cat/resource/xvme-26kg.json?$query=SELECT%0A%20%20%60codi_municip_6%60%2C%0A%20%20%60codi_centre%60%2C%0A%20%20%60nom_naturalesa%60%2C%0A%20%20sum(%60matr_cules_total%60)%20AS%20%60sum_matr_cules_total%60%0AWHERE%20(%60any%60%20IN%20(%22[[[year]]]%22))%20AND%20starts_with(%60codi_municip_6%60%2C%20%2208%22)%0AGROUP%20BY%20%60codi_municip_6%60%2C%20%60codi_centre%60%2C%20%60nom_naturalesa%60%20LIMIT%2010000',
                urlInfo: 'https://analisi.transparenciacatalunya.cat/resource/xvme-26kg.json?$query=SELECT%20%60any%60%20GROUP%20BY%20%60any%60%20ORDER%20BY%20%60any%60%20DESC%20NULL%20LAST',
                extra: [
                    'import' => '4a1',
                    'url2' => 'https://analisi.transparenciacatalunya.cat/resource/2ip7-jdgh.json?$query=SELECT%20%60codi_municipi_6%60%2C%20%60codi_centre%60%2C%20sum(%60total%60)%20AS%20%60sum_total%60%0AWHERE%20caseless_starts_with(%60curs%60%2C%20%22[[[year]]]%22)%0AGROUP%20BY%20%60codi_municipi_6%60%2C%20%60codi_centre%60%0AHAVING%20starts_with(%60codi_municipi_6%60%2C%20%2208%22)%20LIMIT%2010000',
                    'urlInfo2' => 'https://analisi.transparenciacatalunya.cat/resource/2ip7-jdgh.json?$query=SELECT%20%60curs%60%20GROUP%20BY%20%60curs%60%20ORDER%20BY%20%60curs%60%20DESC%20NULL%20FIRST',
                ],
            ),
            '1.2.3' => new IndicatorDefinition(
                indicatorId: '1.2.3',
                targetId: '1.2',
                targetName: "Reduir la proporció de població que viu en la pobresa, augmentant els programes integrals que l'abordin en totes les seves dimensions.",
                sdg: 1,
                indicatorName: 'Nombre de socis de cooperatives per 1.000 habitants',
                indicatorDescription: 'Nombre de socis de cooperatives registrades per 1.000 habitants',
                sign: true,
                source: 'DO_DO',
                unit: 'abs',
                scale: 1,
                url: 'https://analisi.transparenciacatalunya.cat/resource/euku-fzbx.json?$query=SELECT%0A%20%20%60municipi%60%2C%0A%20%20sum(%60total_socis_inicials%60)%20AS%20%60sum_total_socis_inicials%60%0AGROUP%20BY%20%60municipi%60',
                extra: ['import' => 'simple', 'munBy' => 'name', 'munField' => 'municipi', 'field1' => 'sum_total_socis_inicials', 'currentYear' => true, 'fillMissing' => true],
            ),
            '12.2.1' => new IndicatorDefinition(
                indicatorId: '12.2.1',
                targetId: '12.2',
                targetName: 'Assolir la gestió sostenible i un ús eficient dels recursos naturals.',
                sdg: 12,
                indicatorName: "Emissions de CO2 d'edificis per habitant",
                indicatorDescription: "Emissions de CO2 d'edificis per habitant",
                sign: true,
                source: 'DO_DO',
                unit: 'abs',
                scale: 1,
                url: 'https://analisi.transparenciacatalunya.cat/resource/j6ii-t3w2.json?$query=SELECT%0A%20%20%60codi_poblacio%60%2C%0A%20%20sum%28%60emissions_de_co2%60%20%2A%20%60metres_cadastre%60%29%20AS%20%60sum_emissions_de_co2%60%2C%0A%20%20sum%28%60metres_cadastre%60%29%20AS%20%60sum_metres_cadastre%60%0AWHERE%20caseless_starts_with%28%60codi_poblacio%60%2C%20%2208%22%29%0AGROUP%20BY%20%60codi_poblacio%60',
                extra: ['import' => 'simple', 'munBy' => 'code', 'munField' => 'codi_poblacio', 'field1' => 'sum_emissions_de_co2', 'currentYear' => true, 'populationFallback' => true],
            ),
            '17.17.2' => new IndicatorDefinition(
                indicatorId: '17.17.2',
                targetId: '17.17',
                targetName: "Promoure la constitució d'aliances eficaces en els àmbits públic, publicoprivat i de la societat civil, aprofitant les estratègies d'obtenció de recursos dels partenariats.",
                sdg: 17,
                indicatorName: 'Nombre entitats dividit per població',
                indicatorDescription: 'Nombre entitats dividit per població',
                sign: true,
                source: 'DO_DO',
                unit: 'abs',
                scale: 1,
                url: 'https://analisi.transparenciacatalunya.cat/resource/y6fz-g3ff.json?$query=SELECT%0A%20%20date_trunc_y(%60data_inscripcio%60)%20AS%20%60by_year_data_inscripcio%60%2C%0A%20%20%60nom_poblacio%60%2C%0A%20%20count(*)%20AS%20%60count%60%0AWHERE%20caseless_one_of(%60nom_provincia%60%2C%20%22Barcelona%22)%0AGROUP%20BY%20date_trunc_y(%60data_inscripcio%60)%2C%20%60nom_poblacio%60%0AHAVING%0A%20%20%60by_year_data_inscripcio%60%0A%20%20%20%20%3D%20%22[[[year]]]-01-01T00%3A00%3A00%22%20%3A%3A%20floating_timestamp',
                urlInfo: 'https://analisi.transparenciacatalunya.cat/resource/y6fz-g3ff.json?$query=SELECT%20date_trunc_y(%60data_inscripcio%60)%20AS%20%60by_year_data_inscripcio%60%0AWHERE%20caseless_one_of(%60nom_provincia%60%2C%20%22Barcelona%22)%0AGROUP%20BY%20date_trunc_y(%60data_inscripcio%60)%0AHAVING%0A%20%20%60by_year_data_inscripcio%60%0A%20%20%20%20%3E%20%221980-01-01T00%3A00%3A00%22%20%3A%3A%20floating_timestamp',
                extra: ['import' => 'accumulated', 'munBy' => 'name', 'munField' => 'nom_poblacio', 'field1' => 'count', 'yearKey' => 'by_year_data_inscripcio', 'yearExtract4' => true, 'sortYearsAsc' => true],
            ),
            '17.1.2' => new IndicatorDefinition(
                indicatorId: '17.1.2',
                targetId: '17.1',
                targetName: "Enfortir l'autonomia i la capacitat econòmica i fiscal dels governs locals.",
                sdg: 17,
                indicatorName: "Recaptació prevista als pressupostos municipals per l'impost d'activitats econòmiques i l'impost sobre béns immobles en €/hab.",
                indicatorDescription: "Recaptació prevista als pressupostos municipals per l'impost d'activitats econòmiques i l'impost sobre béns immobles en €/hab.",
                sign: true,
                source: 'DO_DO',
                unit: 'abs',
                scale: 1,
                url: 'https://analisi.transparenciacatalunya.cat/resource/4g9s-gzp6.json?$query=SELECT%20%60codi_ens%60%2C%20sum(%60import%60)%20AS%20%60sum_import%60%2C%20%60nom_complert%60%0AWHERE%0A%20%20caseless_starts_with(%60codi_ens%60%2C%20%228%22)%0A%20%20AND%20caseless_eq(%60any_exercici%60%2C%20%22[[[year]]]%22)%0A%20%20AND%20caseless_one_of(%60tipus_partida%60%2C%20%22I%22)%0A%20%20AND%20caseless_starts_with(%60nom_complert%60%2C%20%22ajuntament%22)%20AND%20caseless_one_of(%60codi_pantalla%60%2C%20%22113%22%2C%20%22112%22%2C%20%22114%22%2C%20%22130%22)%0AGROUP%20BY%20%60codi_ens%60%2C%20%60nom_complert%60',
                urlInfo: 'https://analisi.transparenciacatalunya.cat/resource/4g9s-gzp6.json?$query=SELECT%20%60any_exercici%60%0AGROUP%20BY%20%60any_exercici%60%0AORDER%20BY%20%60any_exercici%60%20DESC%20NULL%20LAST',
                extra: ['import' => 'simple', 'munBy' => 'codi_ens_short', 'munField' => 'codi_ens', 'field1' => 'sum_import', 'minYear' => 2010, 'populationFallback' => true],
            ),
            '11.4.2' => new IndicatorDefinition(
                indicatorId: '11.4.2',
                targetId: '11.4',
                targetName: 'Redoblar els esforços per protegir i salvaguardar el patrimoni cultural i natural.',
                sdg: 11,
                indicatorName: 'Equipaments culturals per població',
                indicatorDescription: 'Equipaments culturals per població',
                sign: true,
                source: 'DO_DO',
                unit: 'abs',
                scale: 1,
                url: 'https://analisi.transparenciacatalunya.cat/resource/48s6-82h2.json?$query=SELECT%20%60id_municipi%60%2C%20count(*)%0AWHERE%20caseless_one_of(%60id_prov_ncia%60%2C%20%2208%22)%0AGROUP%20BY%20%60id_municipi%60',
                extra: ['import' => 'simple', 'munBy' => 'code', 'munField' => 'id_municipi', 'field1' => 'count', 'currentYear' => true, 'populationFallback' => true],
            ),
        ];
    }

    protected function import(IndicatorDefinition $def, Indicator $indicator, EtlContext $context): void
    {
        $years = $this->resolveYears($def);

        if ($this->shouldImport(ImportScope::Municipality)) {
            match ($def->extra['import']) {
                'accumulated' => $this->importAccumulated($def, $indicator, $years),
                '4a1' => $this->import4A1($def, $indicator, $years),
                default => $this->importSimple($def, $indicator, $years),
            };
        }
    }

    // -------------------------------------------------------------------------
    // Simple import
    // -------------------------------------------------------------------------

    private function importSimple(IndicatorDefinition $def, Indicator $indicator, array $years): void
    {
        $extra = $def->extra;

        foreach ($years as $year) {
            $this->logger->debug(sprintf('Importing %s year %s', $def->indicatorId, $year));

            // For timestamp-format years (12.1.1), extract 4-digit year before population lookup.
            $actualYear = !empty($extra['yearExtract4'])
                ? (int) substr((string) $year, 0, 4)
                : (int) $year;

            $populations = $this->getPopulations($actualYear, $extra);

            $url = str_replace('[[[year]]]', (string) $year, $def->url);
            $data = $this->http->request('GET', $url)->toArray();

            foreach ($data as $row) {
                [$mun, $munCode6] = $this->resolveMun($row, $extra);
                if (!$mun) {
                    continue;
                }

                if (!empty($extra['skipShortCode']) && 6 !== strlen($munCode6)) {
                    continue;
                }

                $value = $this->extractValue($row, $def);
                if (null === $value) {
                    continue;
                }

                $population = $populations[$munCode6] ?? null;
                if (!$population) {
                    $this->logger->error(sprintf('Population not found for %s year %s', $munCode6, $actualYear));
                    continue;
                }

                $this->setMunicipalityValue($def, $indicator, $mun, $actualYear, (float) $value, (float) $population);
            }

            $this->em->flush();

            if (!empty($extra['fillMissing'])) {
                $minYear = $extra['fillMissingMinYear'] ?? 0;
                if ($actualYear >= $minYear) {
                    foreach ($this->em->getRepository(\App\Entity\MunicipalityValue::class)->findMunicipalitiesWithoutValue($actualYear, $indicator) as $missing) {
                        $pop = $populations[$missing->getMunicipalityCode6()] ?? null;
                        $this->setMunicipalityValue($def, $indicator, $missing, $actualYear, 0.0, null !== $pop ? (float) $pop : null);
                    }
                    $this->em->flush();
                }
            }

            gc_collect_cycles();
        }
    }

    // -------------------------------------------------------------------------
    // Accumulated import — 17.17.2
    // -------------------------------------------------------------------------

    private function importAccumulated(IndicatorDefinition $def, Indicator $indicator, array $years): void
    {
        $extra = $def->extra;

        foreach ($years as $year) {
            $this->logger->debug(sprintf('Importing %s year %s', $def->indicatorId, $year));

            $actualYear = !empty($extra['yearExtract4'])
                ? (int) substr((string) $year, 0, 4)
                : (int) $year;

            $url = str_replace('[[[year]]]', (string) $year, $def->url);
            $data = $this->http->request('GET', $url)->toArray();

            $populations = $this->getPopulations($actualYear, $extra);

            foreach ($data as $row) {
                [$mun, $munCode6] = $this->resolveMun($row, $extra);
                if (!$mun) {
                    continue;
                }

                $value = $this->extractValue($row, $def);
                if (null === $value) {
                    continue;
                }

                $population = $populations[$munCode6] ?? null;
                if (!$population) {
                    continue;
                }

                $prevRow = $this->em->getRepository(\App\Entity\MunicipalityValue::class)->findClosestPreviousYear($actualYear, $mun, $indicator);
                $value = ($prevRow?->getValue() ?? 0) + (float) $value;

                $this->setMunicipalityValue($def, $indicator, $mun, $actualYear, $value, (float) $population);
            }

            $this->em->flush();
            gc_collect_cycles();
        }
    }

    // -------------------------------------------------------------------------
    // 4.a.1 — Students per teacher (cross-reference two APIs)
    // -------------------------------------------------------------------------

    private function import4A1(IndicatorDefinition $def, Indicator $indicator, array $years): void
    {
        $extra = $def->extra;

        foreach ($years as $year) {
            $this->logger->debug(sprintf('Importing 4.a.1 year %s', $year));

            $studentsData = $this->http->request('GET', str_replace('[[[year]]]', (string) $year, $def->url))->toArray();
            $teachersData = $this->http->request('GET', str_replace('[[[year]]]', (string) $year, $extra['url2']))->toArray();

            $teachersByCentre = [];
            foreach ($teachersData as $row) {
                $teachersByCentre[$row['codi_centre']] = $row['sum_total'];
            }

            $studentsByMun = [];
            $teachersByMun = [];

            foreach ($studentsData as $row) {
                $munCode6 = $row['codi_municip_6'];
                $codiCentre = $row['codi_centre'];

                if (!isset($teachersByCentre[$codiCentre])) {
                    continue;
                }

                $studentsByMun[$munCode6] = ($studentsByMun[$munCode6] ?? 0) + (float) $row['sum_matr_cules_total'];
                $teachersByMun[$munCode6] = ($teachersByMun[$munCode6] ?? 0) + (float) $teachersByCentre[$codiCentre];
            }

            foreach ($studentsByMun as $munCode6 => $students) {
                $teachers = $teachersByMun[$munCode6] ?? 0;
                if (!$teachers) {
                    continue;
                }

                $mun = $this->getMunicipalityByCode($munCode6);
                if (!$mun) {
                    continue;
                }

                $this->setMunicipalityValue($def, $indicator, $mun, (int) $year, $students, $teachers);
            }

            $this->em->flush();
            gc_collect_cycles();
        }
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function resolveYears(IndicatorDefinition $def): array
    {
        $extra = $def->extra;

        if (!empty($extra['currentYear'])) {
            return [(string) date('Y')];
        }

        $years = $this->do->getYears($def->urlInfo);

        if ('4.a.1' === $def->indicatorId) {
            $years2 = $this->do->getYears($extra['urlInfo2']);
            $years = array_values(array_intersect($years, $years2));
        }

        if (!empty($extra['sortYearsAsc'])) {
            sort($years);
        }

        if (!empty($extra['minYear'])) {
            $years = array_values(array_filter($years, fn ($y) => (int) $y >= $extra['minYear']));
        }

        return $years;
    }

    private function getPopulations(int|string $year, array $extra): array
    {
        $populations = $this->do->getMunicipalityPopulationByYear((string) $year);

        if (empty($populations) && !empty($extra['populationFallback'])) {
            $populations = $this->do->getMunicipalityPopulationLastYear();
        }

        return $populations;
    }

    /** Returns [Municipality|null, string $munCode6] */
    private function resolveMun(array $row, array $extra): array
    {
        $munBy = $extra['munBy'];
        $munField = $extra['munField'];

        if ('name' === $munBy) {
            $mun = $this->geo->getMunicipalityByName($row[$munField] ?? '');

            return [$mun, $mun ? $mun->getMunicipalityCode6() : ''];
        }

        if ('codi_ens_short' === $munBy) {
            // "800180001" → "0" + first-4 chars = "08001" (5-digit INE code)
            $code = '0'.substr($row[$munField] ?? '', 0, 4);
            $mun = $this->getMunicipalityByCode($code);

            return [$mun, $mun ? $mun->getMunicipalityCode6() : ''];
        }

        $mun = $this->getMunicipalityByCode($row[$munField] ?? '');

        return [$mun, $mun ? $mun->getMunicipalityCode6() : ''];
    }

    private function extractValue(array $row, IndicatorDefinition $def): ?float
    {
        $field1 = $def->extra['field1'];

        // 3.6.1 / 11.2.1: value = deaths + serious injuries
        if ('__traffic_deaths' === $field1) {
            return (float) ($row['sum_f_morts'] ?? 0) + (float) ($row['sum_f_ferits_greus'] ?? 0);
        }

        $v = $row[$field1] ?? null;

        return null !== $v ? (float) $v : null;
    }
}
