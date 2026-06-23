<?php

namespace App\Service\Etl\Importer;

use App\Entity\Comarca;
use App\Entity\Indicator;
use App\Entity\Municipality;
use App\Service\Etl\Dto\EtlContext;
use App\Service\Etl\Dto\IndicatorDefinition;
use App\Service\Etl\Enum\ImportScope;
use App\Service\Etl\Util\EtlUtils;

/**
 * Handles indicators fetched from the IDESCAT SSV table format (?...&f=ssv).
 *
 * Dispatch:
 *   importSimple     — 1.3.1, 2.3.2, 5.c.1, 8.3.1
 *   import142        — 1.4.2 (two URLs per geo level; no-year province)
 *   importCensAg     — 2.3.3, 2.3.4 (column sums + older census datasets)
 *   import342        — 3.4.2 (weighted avg age; municipality backfill from comarca)
 *   import511        — 5.1.1 (women/men unemployment ratio via affiliates)
 *   importSeparately — 8.5.1, 8.5.2 (per-entity API calls; hardcoded years)
 *   import891        — 8.9.1 (5-URL accumulation: hotels + camping + rural + apartments + houses)
 */
final class IdescatTableImporter extends AbstractEtlImporter
{
    private const SEPARATELY_YEARS = [2024, 2023, 2022, 2021, 2020, 2019, 2018, 2017, 2016, 2015];

    protected function getDefinitions(): array
    {
        return [
            '1.3.1' => new IndicatorDefinition(
                indicatorId: '1.3.1',
                targetId: '1.3',
                targetName: "Reforçar en l'àmbit local sistemes i mesures apropiades de protecció social per a totes les persones, aconseguint una àmplia cobertura de les persones vulnerables.",
                sdg: 1,
                indicatorName: 'Renda Gar. Ciut. per 10.000 hab.',
                indicatorDescription: "Mitjana de persones que han estat beneficiaries de la Renda Garantida de Ciutadania a l'any de referència, per cada 10.000 habitants",
                sign: true,
                source: 'IDESCAT_TABLE',
                unit: 'renda',
                scale: 10000,
                url: 'https://www.idescat.cat/pub/?id=ris&n=9549&geo=mun&t=[[[year]]]00&f=ssv',
                urlInfo: 'https://www.idescat.cat/pub/?id=ris&n=9549&geo=cat&f=ssv',
                urlComarca: 'https://www.idescat.cat/pub/?id=ris&n=9549&geo=com&t=[[[year]]]00&f=ssv',
                urlProv: 'https://www.idescat.cat/pub/?id=ris&n=9549&geo=prov&t=[[[year]]]00&f=ssv',
                extra: ['valueKey' => 'Mitjana de persones beneficiàries', 'popSource' => 'do'],
            ),
            '1.4.2' => new IndicatorDefinition(
                indicatorId: '1.4.2',
                targetId: '1.4',
                targetName: 'Garantir que les persones, especialment les pobres i vulnerables, tinguin els mateixos drets, així com accés als serveis bàsics, recursos naturals, econòmics i financers en igualtat de condicions.',
                sdg: 1,
                indicatorName: 'Proporció de persones amb discapacitat que reben una pensió',
                indicatorDescription: 'Proporció de persones amb discapacitat que reben una pensió',
                sign: true,
                source: 'IDESCAT_TABLE',
                unit: 'percent',
                scale: 1,
                url: 'https://www.idescat.cat/pub/?id=ppsr&n=9383&geo=mun&t=[[[year]]]00&f=ssv',
                urls: ['https://www.idescat.cat/pub/?id=regdis&n=441&geo=mun&t=[[[year]]]00&f=ssv'],
                urlInfo: 'https://www.idescat.cat/pub/?id=ppsr&n=9383&geo=prov%3A08&f=ssv',
                urlsInfo: ['https://www.idescat.cat/pub/?id=regdis&n=441&geo=prov%3A08&f=ssv'],
                urlComarca: 'https://www.idescat.cat/pub/?id=ppsr&n=9383&geo=com&t=[[[year]]]00&f=ssv',
                urlProv: 'https://www.idescat.cat/pub/?id=ppsr&n=9383&geo=prov%3A08&f=ssv',
                extra: [
                    'urlsComarca' => ['https://www.idescat.cat/pub/?id=regdis&n=441&geo=com&t=[[[year]]]00&f=ssv'],
                    'urlsProv' => ['https://www.idescat.cat/pub/?id=regdis&n=441&geo=prov%3A08&f=ssv'],
                ],
            ),
            '2.3.2' => new IndicatorDefinition(
                indicatorId: '2.3.2',
                targetId: '2.3',
                targetName: "Fomentar la productivitat agrícola i els ingressos de les persones que es dediquen a la producció d'aliments a petita escala.",
                sdg: 2,
                indicatorName: 'Atur en agricultura',
                indicatorDescription: "Atur en agricultura respecte l'atur total",
                sign: true,
                source: 'IDESCAT_TABLE',
                unit: 'percent',
                scale: 1,
                url: 'https://www.idescat.cat/pub/?id=atureg&n=4302&geo=mun&t=[[[year]]]00&f=ssv',
                urlInfo: 'https://www.idescat.cat/pub/?id=atureg&n=4302&geo=prov%3A08&f=ssv',
                urlComarca: 'https://www.idescat.cat/pub/?id=atureg&n=4302&geo=com&t=[[[year]]]00&f=ssv',
                urlProv: 'https://www.idescat.cat/pub/?id=atureg&n=4302&geo=prov%3A08&f=ssv',
                extra: ['valueKey' => 'Agricultura', 'value2Key' => 'Total'],
            ),
            '2.3.3' => new IndicatorDefinition(
                indicatorId: '2.3.3',
                targetId: '2.3',
                targetName: "Fomentar la productivitat agrícola i els ingressos de les persones que es dediquen a la producció d'aliments a petita escala.",
                sdg: 2,
                indicatorName: "% Hectàrees d'explotacions de petita escala",
                indicatorDescription: "% Hectàrees d'explotacions de petita escala",
                sign: true,
                source: 'IDESCAT_TABLE',
                unit: 'percent',
                scale: 1,
                url: 'https://www.idescat.cat/pub/?id=censag&n=16090&geo=mun&t=[[[year]]]00&f=ssv',
                urlInfo: 'https://www.idescat.cat/pub/?id=censag&n=16090&geo=prov%3A08&f=ssv',
                urlComarca: 'https://www.idescat.cat/pub/?id=censag&n=16090&geo=com&t=[[[year]]]00&f=ssv',
                urlProv: 'https://www.idescat.cat/pub/?id=censag&n=16090&geo=prov%3A08&f=ssv',
                extra: [
                    'valueSumKeys' => ["Menys d'1 ha. Ha", "D'1 a menys de 2 ha. Ha", 'De 2 a menys de 5 ha. Ha', 'De 5 a menys de 10 ha. Ha'],
                    'value2Key' => 'Total amb terres. Ha',
                    'urlsMoreYears' => [
                        'https://www.idescat.cat/pub/?id=censag&n=5099&geo=mun&t=[[[year]]]00&f=ssv',
                        'https://www.idescat.cat/pub/?id=censag&n=484&geo=mun&t=[[[year]]]00&f=ssv',
                    ],
                    'urlsComarcaMoreYears' => [
                        'https://www.idescat.cat/pub/?id=censag&n=5099&geo=com&t=[[[year]]]00&f=ssv',
                        'https://www.idescat.cat/pub/?id=censag&n=484&geo=com&t=[[[year]]]00&f=ssv',
                    ],
                    'urlsProvMoreYears' => [
                        'https://www.idescat.cat/pub/?id=censag&n=5099&geo=prov%3A08&f=ssv',
                        'https://www.idescat.cat/pub/?id=censag&n=484&geo=prov%3A08&f=ssv',
                    ],
                    'urlsInfoMoreYears' => [
                        'https://www.idescat.cat/pub/?id=censag&n=5099&geo=prov%3A08&f=ssv',
                        'https://www.idescat.cat/pub/?id=censag&n=484&geo=prov%3A08&f=ssv',
                    ],
                ],
            ),
            '2.3.4' => new IndicatorDefinition(
                indicatorId: '2.3.4',
                targetId: '2.3',
                targetName: "Fomentar la productivitat agrícola i els ingressos de les persones que es dediquen a la producció d'aliments a petita escala.",
                sdg: 2,
                indicatorName: "% d'explotacions de petita escala",
                indicatorDescription: "% d'explotacions de petita escala",
                sign: true,
                source: 'IDESCAT_TABLE',
                unit: 'percent',
                scale: 1,
                url: 'https://www.idescat.cat/pub/?id=censag&n=16090&geo=mun&t=[[[year]]]00&f=ssv',
                urlInfo: 'https://www.idescat.cat/pub/?id=censag&n=16090&geo=prov%3A08&f=ssv',
                urlComarca: 'https://www.idescat.cat/pub/?id=censag&n=16090&geo=com&t=[[[year]]]00&f=ssv',
                urlProv: 'https://www.idescat.cat/pub/?id=censag&n=16090&geo=prov%3A08&f=ssv',
                extra: [
                    'valueSumKeys' => ["Menys d'1 ha. Explot.", "D'1 a menys de 2 ha. Explot.", 'De 2 a menys de 5 ha. Explot.', 'De 5 a menys de 10 ha. Explot.'],
                    'value2Key' => 'Total amb terres. Explot.',
                    'urlsMoreYears' => [
                        'https://www.idescat.cat/pub/?id=censag&n=5099&geo=mun&t=[[[year]]]00&f=ssv',
                        'https://www.idescat.cat/pub/?id=censag&n=484&geo=mun&t=[[[year]]]00&f=ssv',
                    ],
                    'urlsComarcaMoreYears' => [
                        'https://www.idescat.cat/pub/?id=censag&n=5099&geo=com&t=[[[year]]]00&f=ssv',
                        'https://www.idescat.cat/pub/?id=censag&n=484&geo=com&t=[[[year]]]00&f=ssv',
                    ],
                    'urlsProvMoreYears' => [
                        'https://www.idescat.cat/pub/?id=censag&n=5099&geo=prov%3A08&f=ssv',
                        'https://www.idescat.cat/pub/?id=censag&n=484&geo=prov%3A08&f=ssv',
                    ],
                    'urlsInfoMoreYears' => [
                        'https://www.idescat.cat/pub/?id=censag&n=5099&geo=prov%3A08&f=ssv',
                        'https://www.idescat.cat/pub/?id=censag&n=484&geo=prov%3A08&f=ssv',
                    ],
                ],
            ),
            '3.4.2' => new IndicatorDefinition(
                indicatorId: '3.4.2',
                targetId: '3.4',
                targetName: 'Incrementar programes de prevenció i tractament de malalties no transmissibles adreçades a la reducció de la mortalitat prematura i promoure la salut mental i el benestar.',
                sdg: 3,
                indicatorName: 'Edat mitjana defuncions',
                indicatorDescription: 'Edat mitjana defuncions',
                sign: true,
                source: 'IDESCAT_TABLE',
                unit: 'percent',
                scale: 1,
                url: 'https://www.idescat.cat/pub/?id=def&n=269&geo=mun&col=3&t=[[[year]]]00&f=ssv',
                urlInfo: 'https://www.idescat.cat/pub/?id=def&n=269&fil=103&col=3&f=ssv',
                urlComarca: 'https://www.idescat.cat/pub/?id=def&n=269&geo=com&col=3&t=[[[year]]]00&f=ssv',
                urlProv: 'https://www.idescat.cat/pub/?id=def&n=269&geo=prov&col=3&t=[[[year]]]00&f=ssv',
            ),
            '5.1.1' => new IndicatorDefinition(
                indicatorId: '5.1.1',
                targetId: '5.1',
                targetName: 'Posar fi a totes les formes de discriminació de gènere.',
                sdg: 5,
                indicatorName: 'Diferència atur dones vs homes',
                indicatorDescription: "Taxa d'atur dones - Taxa d'atur homes",
                sign: true,
                source: 'IDESCAT',
                unit: 'percent',
                scale: 1,
                url: 'https://www.idescat.cat/pub/?id=atureg&n=4299&geo=mun&t=[[[year]]]00&f=ssv',
                urlInfo: 'https://www.idescat.cat/pub/?id=atureg&n=4299&geo=prov%3A08&f=ssv',
                urlComarca: 'https://www.idescat.cat/pub/?id=atureg&n=4299&geo=com&t=[[[year]]]00&f=ssv',
                urlProv: 'https://www.idescat.cat/pub/?id=atureg&n=4299&geo=prov&t=[[[year]]]00&f=ssv',
                extra: ['yearMin' => 2012],
            ),
            '5.c.1' => new IndicatorDefinition(
                indicatorId: '5.c.1',
                targetId: '5.c',
                targetName: "Enfortir les polítiques i els plans de igualtat de gènere i d'empoderament de dones i nenes.",
                sdg: 5,
                indicatorName: 'Taxa atur en dones',
                indicatorDescription: '',
                sign: true,
                source: 'IDESCAT_TABLE',
                unit: 'percent',
                scale: 1,
                url: 'https://www.idescat.cat/pub/?id=atureg&n=4299&geo=mun&t=[[[year]]]00&f=ssv',
                urlInfo: 'https://www.idescat.cat/pub/?id=atureg&n=4299&geo=prov%3A08&f=ssv',
                urlComarca: 'https://www.idescat.cat/pub/?id=atureg&n=4299&geo=com&t=[[[year]]]00&f=ssv',
                urlProv: 'https://www.idescat.cat/pub/?id=atureg&n=4299&geo=prov&t=[[[year]]]00&f=ssv',
                extra: ['valueKey' => 'Sexe. Dones', 'popSource' => 'affiliates_f', 'yearMin' => 2012],
            ),
            '8.3.1' => new IndicatorDefinition(
                indicatorId: '8.3.1',
                targetId: '8.3',
                targetName: "Promoure la creació d'ocupació digna a través de la innovació, creativitat i emprenedoria, fomentant el creixement de les petites i mitjanes empreses.",
                sdg: 8,
                indicatorName: "Taxa d'ocupació",
                indicatorDescription: "Nombre d'assalariats i autòmoms ocupats residents en un municipi respecte la població de 16 a 64 anys del municipi",
                sign: true,
                source: 'IDESCAT_TABLE',
                unit: 'percent',
                scale: 1,
                url: 'https://www.idescat.cat/pub/?id=afi&n=8604&geo=mun&t=[[[year]]]12&f=ssv',
                urlInfo: 'https://www.idescat.cat/pub/?id=afi&n=8604&geo=cat&f=ssv',
                urlComarca: 'https://www.idescat.cat/pub/?id=afi&n=8604&geo=com&t=[[[year]]]12&f=ssv',
                urlProv: 'https://www.idescat.cat/pub/?id=afi&n=8604&geo=prov&t=[[[year]]]12&f=ssv',
                extra: [
                    'valueKey' => 'Total',
                    'popSource' => 'idescat_ages',
                    'popAges' => 'Y016,Y017,Y018,Y019,Y020,Y021,Y022,Y023,Y024,Y025,Y026,Y027,Y028,Y029,Y030,Y031,Y032,Y033,Y034,Y035,Y036,Y037,Y038,Y039,Y040,Y041,Y042,Y043,Y044,Y045,Y046,Y047,Y048,Y049,Y050,Y051,Y052,Y053,Y054,Y055,Y056,Y057,Y058,Y059,Y060,Y061,Y062,Y063,Y064',
                ],
            ),
            '8.5.1' => new IndicatorDefinition(
                indicatorId: '8.5.1',
                targetId: '8.5',
                targetName: "Aconseguir una alta taxa d'ocupació i de qualitat d'aquells col·lectius més vulnerables en el conjunt del territori, així com la igualtat de remuneració per treball d'igual valor.",
                sdg: 8,
                indicatorName: '% Persones aturades > 45 anys',
                indicatorDescription: '% Persones aturades > 45 anys',
                sign: true,
                source: 'IDESCAT_TABLE',
                unit: 'percent',
                scale: 1,
                url: 'https://www.idescat.cat/pub/?id=atureg&n=4300&geo=mun%3A[[[ine6]]]&t=[[[year]]]00&f=ssv',
                urlComarca: 'https://www.idescat.cat/pub/?id=atureg&n=4300&geo=com%3A[[[comarca]]]&t=[[[year]]]00&f=ssv',
                urlProv: 'https://www.idescat.cat/pub/?id=atureg&n=4300&geo=prov%3A08&t=[[[year]]]00&f=ssv',
                extra: [
                    'ages' => 'Y045,Y046,Y047,Y048,Y049,Y050,Y051,Y052,Y053,Y054,Y055,Y056,Y057,Y058,Y059,Y060,Y061,Y062,Y063,Y064',
                    'valueBands' => ['De 45 a 49 anys', 'De 50 a 54 anys', 'De 55 a 59 anys', 'De 60 anys i més'],
                ],
            ),
            '8.5.2' => new IndicatorDefinition(
                indicatorId: '8.5.2',
                targetId: '8.5',
                targetName: "Aconseguir una alta taxa d'ocupació i de qualitat d'aquells col·lectius més vulnerables en el conjunt del territori, així com la igualtat de remuneració per treball d'igual valor.",
                sdg: 8,
                indicatorName: '% Persones aturades < 25 anys',
                indicatorDescription: '% Persones aturades < 25 anys',
                sign: true,
                source: 'IDESCAT_TABLE',
                unit: 'percent',
                scale: 1,
                url: 'https://www.idescat.cat/pub/?id=atureg&n=4300&geo=mun%3A[[[ine6]]]&t=[[[year]]]00&f=ssv',
                urlComarca: 'https://www.idescat.cat/pub/?id=atureg&n=4300&geo=com%3A[[[comarca]]]&t=[[[year]]]00&f=ssv',
                urlProv: 'https://www.idescat.cat/pub/?id=atureg&n=4300&geo=prov%3A08&t=[[[year]]]00&f=ssv',
                extra: [
                    'ages' => 'Y016,Y017,Y018,Y019,Y020,Y021,Y022,Y023,Y024',
                    'valueBands' => ['De 16 a 19 anys', 'De 20 a 24 anys'],
                ],
            ),
            '8.9.1' => new IndicatorDefinition(
                indicatorId: '8.9.1',
                targetId: '8.9',
                targetName: "Reforçar les polítiques de promoció d'un turisme sostenible, de proximitat, qualitat i que creï ocupació i promogui la cultura i els productes locals.",
                sdg: 8,
                indicatorName: 'Pressió turística',
                indicatorDescription: 'Ratio de places turístiques per 10.000 habitants',
                sign: true,
                source: 'IDESCAT_TABLE',
                unit: 'percent',
                scale: 1,
                url: 'https://www.idescat.cat/pub/?id=turall&n=6031&geo=mun&t=[[[year]]]00&f=ssv',
                urls: [
                    'https://www.idescat.cat/pub/?id=turall&n=6036&geo=mun&t=[[[year]]]00&f=ssv',
                    'https://www.idescat.cat/pub/?id=turall&n=6039&geo=mun&t=[[[year]]]00&f=ssv',
                    'https://www.idescat.cat/pub/?id=turall&n=16721&geo=mun&t=[[[year]]]00&f=ssv',
                    'https://www.idescat.cat/pub/?id=turall&n=16722&geo=mun&t=[[[year]]]00&f=ssv',
                ],
                urlInfo: 'https://www.idescat.cat/pub/?id=turall&n=6031&f=ssv',
                urlsInfo: [
                    'https://www.idescat.cat/pub/?id=turall&n=6036&f=ssv',
                    'https://www.idescat.cat/pub/?id=turall&n=6039&f=ssv',
                    'https://www.idescat.cat/pub/?id=turall&n=16721&f=ssv',
                    'https://www.idescat.cat/pub/?id=turall&n=16722&f=ssv',
                ],
                urlComarca: 'https://www.idescat.cat/pub/?id=turall&n=6031&geo=com&t=[[[year]]]00&f=ssv',
                urlProv: 'https://www.idescat.cat/pub/?id=turall&n=6031&geo=prov&t=[[[year]]]00&f=ssv',
                extra: [
                    'urlsComarca' => [
                        'https://www.idescat.cat/pub/?id=turall&n=6036&geo=com&t=[[[year]]]00&f=ssv',
                        'https://www.idescat.cat/pub/?id=turall&n=6039&geo=com&t=[[[year]]]00&f=ssv',
                        'https://www.idescat.cat/pub/?id=turall&n=16721&geo=com&t=[[[year]]]00&f=ssv',
                        'https://www.idescat.cat/pub/?id=turall&n=16722&geo=com&t=[[[year]]]00&f=ssv',
                    ],
                    'urlsProv' => [
                        'https://www.idescat.cat/pub/?id=turall&n=6036&geo=prov&t=[[[year]]]00&f=ssv',
                        'https://www.idescat.cat/pub/?id=turall&n=6039&geo=prov&t=[[[year]]]00&f=ssv',
                        'https://www.idescat.cat/pub/?id=turall&n=16721&geo=prov&t=[[[year]]]00&f=ssv',
                        'https://www.idescat.cat/pub/?id=turall&n=16722&geo=prov&t=[[[year]]]00&f=ssv',
                    ],
                ],
            ),
        ];
    }

    // =========================================================================
    // Dispatch
    // =========================================================================

    protected function import(IndicatorDefinition $def, Indicator $indicator, EtlContext $context): void
    {
        match ($def->indicatorId) {
            '1.4.2' => $this->import142($def, $indicator),
            '2.3.3', '2.3.4' => $this->importCensAg($def, $indicator),
            '3.4.2' => $this->import342($def, $indicator),
            '5.1.1' => $this->import511($def, $indicator),
            '8.5.1', '8.5.2' => $this->importSeparately($def, $indicator),
            '8.9.1' => $this->import891($def, $indicator),
            default => $this->importSimple($def, $indicator),
        };
    }

    // =========================================================================
    // importSimple — 1.3.1, 2.3.2, 5.c.1, 8.3.1
    // =========================================================================

    private function importSimple(IndicatorDefinition $def, Indicator $indicator): void
    {
        $extra = $def->extra;
        $years = $this->resolveYears($def);

        // Province
        if ($this->shouldImport(ImportScope::Province) && $def->urlProv) {
            if (!str_contains($def->urlProv, '[[[year]]]')) {
                // All years in one response (e.g. 2.3.2 geo=prov%3A08 without year)
                $province = $this->geo->getProvince();
                if ($province) {
                    $allData = $this->idescatTable->getProvinceValues($def->urlProv);
                    foreach ($allData as $row) {
                        $year = (int) ($row['year'] ?? 0);
                        if (!$year) {
                            continue;
                        }
                        $pops = $this->getPopulations($def, $year, 'PROV');
                        [$value, $value2] = $this->extractSimpleValues($extra, $row, $province->getProvinceCode(), $pops);
                        if (null !== $value) {
                            $this->setProvinceValue($def, $indicator, $province, $year, $value, $value2);
                        }
                    }
                }
            } else {
                // Year-by-year (geo=prov, all provinces — only Barcelona resolves)
                foreach ($years as $year) {
                    $url = str_replace('[[[year]]]', $year, $def->urlProv);
                    $data = $this->idescatTable->getValues($url);
                    $pops = $this->getPopulations($def, (int) $year, 'PROV');
                    foreach ($data as $code => $row) {
                        $prov = $this->geo->getProvinceByCode($code);
                        if (!$prov) {
                            continue;
                        }
                        [$value, $value2] = $this->extractSimpleValues($extra, $row, $prov->getProvinceCode(), $pops);
                        if (null !== $value) {
                            $this->setProvinceValue($def, $indicator, $prov, (int) $year, $value, $value2);
                        }
                    }
                }
            }
            $this->em->flush();
        }

        // Comarca
        if ($this->shouldImport(ImportScope::Comarca) && $def->urlComarca) {
            foreach ($years as $year) {
                $url = str_replace('[[[year]]]', $year, $def->urlComarca);
                $data = $this->idescatTable->getValues($url);
                $pops = $this->getPopulations($def, (int) $year, 'COM');
                foreach ($data as $code => $row) {
                    $comarca = $this->geo->getComarcaByCode($code);
                    if (!$comarca) {
                        continue;
                    }
                    [$value, $value2] = $this->extractSimpleValues($extra, $row, $comarca->getComarcaCode(), $pops);
                    if (null !== $value) {
                        $this->setComarcaValue($def, $indicator, $comarca, (int) $year, $value, $value2);
                    }
                }
                $this->em->flush();
            }
        }

        // Municipality
        if ($this->shouldImport(ImportScope::Municipality)) {
            foreach ($years as $year) {
                $url = str_replace('[[[year]]]', $year, $def->url);
                $data = $this->idescatTable->getValues($url);
                $pops = $this->getPopulations($def, (int) $year, 'MUN');
                foreach ($data as $code => $row) {
                    $mun = $this->getMunicipalityByCode($code);
                    if (!$mun) {
                        continue;
                    }
                    [$value, $value2] = $this->extractSimpleValues($extra, $row, $mun->getMunicipalityCode6(), $pops);
                    if (null !== $value) {
                        $this->setMunicipalityValue($def, $indicator, $mun, (int) $year, $value, $value2);
                    }
                }
                $this->em->flush();
                gc_collect_cycles();
            }
        }
    }

    // =========================================================================
    // import142 — 1.4.2 (pension disability)
    // =========================================================================

    private function import142(IndicatorDefinition $def, Indicator $indicator): void
    {
        $years = $this->resolveYears($def);
        $disabilityMun = $def->urls[0];
        $disabilityCom = $def->extra['urlsComarca'][0];
        $disabilityProv = $def->extra['urlsProv'][0];

        // Province — both URLs have no year → getProvinceValuesFromIDESCATTable
        if ($this->shouldImport(ImportScope::Province)) {
            $province = $this->geo->getProvince();
            $pensionData = $this->idescatTable->getProvinceValues($def->urlProv);
            $disabilityData = $this->idescatTable->getProvinceValues($disabilityProv);
            if ($province) {
                foreach ($pensionData as $yearStr => $row) {
                    $year = (int) ($row['year'] ?? 0);
                    $value = EtlUtils::toFloat($row['Invalidesa'] ?? '');
                    if (!$year || !is_numeric($value)) {
                        continue;
                    }
                    $disRow = $disabilityData[$yearStr] ?? null;
                    $value2raw = $disRow['Sexe. Total'] ?? null;
                    if (!$value2raw) {
                        continue;
                    }
                    $this->setProvinceValue($def, $indicator, $province, $year, $value, EtlUtils::toFloat($value2raw));
                }
            }
            $this->em->flush();
        }

        // Comarca — both URLs have year
        if ($this->shouldImport(ImportScope::Comarca) && $def->urlComarca) {
            foreach ($years as $year) {
                $pensionData = $this->idescatTable->getValues(str_replace('[[[year]]]', $year, $def->urlComarca));
                $disabilityData = $this->idescatTable->getValues(str_replace('[[[year]]]', $year, $disabilityCom));
                foreach ($pensionData as $code => $row) {
                    $comarca = $this->geo->getComarcaByCode($code);
                    if (!$comarca) {
                        continue;
                    }
                    $value = EtlUtils::toFloat($row['Invalidesa'] ?? '');
                    if (!is_numeric($value)) {
                        continue;
                    }
                    $value2raw = $disabilityData[$code]['Sexe. Total'] ?? null;
                    if (!$value2raw) {
                        continue;
                    }
                    $this->setComarcaValue($def, $indicator, $comarca, (int) $year, $value, EtlUtils::toFloat($value2raw));
                }
                $this->em->flush();
            }
        }

        // Municipality — both URLs have year
        if ($this->shouldImport(ImportScope::Municipality)) {
            foreach ($years as $year) {
                $pensionData = $this->idescatTable->getValues(str_replace('[[[year]]]', $year, $def->url));
                $disabilityData = $this->idescatTable->getValues(str_replace('[[[year]]]', $year, $disabilityMun));
                foreach ($pensionData as $code => $row) {
                    $mun = $this->getMunicipalityByCode($code);
                    if (!$mun) {
                        continue;
                    }
                    $value = EtlUtils::toFloat($row['Invalidesa'] ?? '');
                    if (!is_numeric($value)) {
                        continue;
                    }
                    $value2raw = $disabilityData[$code]['Sexe. Total'] ?? null;
                    if (!$value2raw) {
                        continue;
                    }
                    $this->setMunicipalityValue($def, $indicator, $mun, (int) $year, $value, EtlUtils::toFloat($value2raw));
                }
                $this->em->flush();
                gc_collect_cycles();
            }
        }
    }

    // =========================================================================
    // importCensAg — 2.3.3, 2.3.4 (census agriculture; sum of small-scale cols)
    // =========================================================================

    private function importCensAg(IndicatorDefinition $def, Indicator $indicator): void
    {
        $extra = $def->extra;
        $valueSumKeys = $extra['valueSumKeys'];
        $value2Key = $extra['value2Key'];

        // Main year set
        $years = $this->resolveYears($def);
        $this->importCensAgProv($def, $indicator, $def->urlProv, $valueSumKeys, $value2Key);
        $this->importCensAgByYear($def, $indicator, $def->urlComarca, $years, 'COM', $valueSumKeys, $value2Key);
        $this->importCensAgByYear($def, $indicator, $def->url, $years, 'MUN', $valueSumKeys, $value2Key);

        // Older census datasets (each has its own province URL and year set)
        foreach (($extra['urlsInfoMoreYears'] ?? []) as $i => $urlInfoMore) {
            $yearsMore = $this->idescatTable->getYears($urlInfoMore);
            $this->importCensAgProv($def, $indicator, $extra['urlsProvMoreYears'][$i], $valueSumKeys, $value2Key);
            $this->importCensAgByYear($def, $indicator, $extra['urlsComarcaMoreYears'][$i], $yearsMore, 'COM', $valueSumKeys, $value2Key);
            $this->importCensAgByYear($def, $indicator, $extra['urlsMoreYears'][$i], $yearsMore, 'MUN', $valueSumKeys, $value2Key);
        }
    }

    private function importCensAgProv(IndicatorDefinition $def, Indicator $indicator, string $urlProv, array $valueSumKeys, string $value2Key): void
    {
        if (!$this->shouldImport(ImportScope::Province)) {
            return;
        }
        $province = $this->geo->getProvince();
        if (!$province) {
            return;
        }
        $data = $this->idescatTable->getProvinceValues($urlProv);
        foreach ($data as $row) {
            $year = (int) ($row['year'] ?? 0);
            $value = (float) array_sum(array_map(fn ($k) => EtlUtils::toFloat($row[$k] ?? 0), $valueSumKeys));
            $value2 = EtlUtils::toFloat($row[$value2Key] ?? 0);
            if (!$year || 0.0 == $value2) {
                continue;
            }
            $this->setProvinceValue($def, $indicator, $province, $year, $value, $value2);
        }
        $this->em->flush();
    }

    private function importCensAgByYear(IndicatorDefinition $def, Indicator $indicator, string $urlTemplate, array $years, string $scope, array $valueSumKeys, string $value2Key): void
    {
        if ('MUN' === $scope && !$this->shouldImport(ImportScope::Municipality)) {
            return;
        }
        if ('COM' === $scope && !$this->shouldImport(ImportScope::Comarca)) {
            return;
        }

        foreach ($years as $year) {
            $url = str_replace('[[[year]]]', $year, $urlTemplate);
            $data = $this->idescatTable->getValues($url);
            foreach ($data as $code => $row) {
                $value = (float) array_sum(array_map(fn ($k) => EtlUtils::toFloat($row[$k] ?? 0), $valueSumKeys));
                $value2 = EtlUtils::toFloat($row[$value2Key] ?? 0);
                if (0.0 == $value2) {
                    continue;
                }
                if ('MUN' === $scope) {
                    $mun = $this->getMunicipalityByCode($code);
                    if (!$mun) {
                        continue;
                    }
                    $this->setMunicipalityValue($def, $indicator, $mun, (int) $year, $value, $value2);
                } else {
                    $comarca = $this->geo->getComarcaByCode($code);
                    if (!$comarca) {
                        continue;
                    }
                    $this->setComarcaValue($def, $indicator, $comarca, (int) $year, $value, $value2);
                }
            }
            $this->em->flush();
            if ('MUN' === $scope) {
                gc_collect_cycles();
            }
        }
    }

    // =========================================================================
    // import342 — 3.4.2 (weighted-average death age; municipality backfill)
    // =========================================================================

    private function import342(IndicatorDefinition $def, Indicator $indicator): void
    {
        $years = $this->resolveYears($def);

        // Province (geo=prov has year → year-by-year, iterate all provinces)
        if ($this->shouldImport(ImportScope::Province) && $def->urlProv) {
            foreach ($years as $year) {
                $url = str_replace('[[[year]]]', $year, $def->urlProv);
                $data = $this->idescatTable->getValues($url);
                foreach ($data as $code => $row) {
                    $prov = $this->geo->getProvinceByCode($code);
                    if (!$prov) {
                        continue;
                    }
                    $value = $this->calcWeightedAge($row);
                    if (null === $value) {
                        continue;
                    }
                    $this->setProvinceValue($def, $indicator, $prov, (int) $year, $value, null);
                }
            }
            $this->em->flush();
        }

        // Comarca first (with municipality backfill for missing municipalities)
        if ($this->shouldImport(ImportScope::Comarca) && $def->urlComarca) {
            foreach ($years as $year) {
                $url = str_replace('[[[year]]]', $year, $def->urlComarca);
                $data = $this->idescatTable->getValues($url);
                foreach ($data as $code => $row) {
                    $comarca = $this->geo->getComarcaByCode($code);
                    if (!$comarca) {
                        continue;
                    }
                    $value = $this->calcWeightedAge($row);
                    if (null === $value) {
                        continue;
                    }
                    $this->setComarcaValue($def, $indicator, $comarca, (int) $year, $value, null);
                    // Backfill all municipalities in this comarca
                    if ($this->shouldImport(ImportScope::Municipality)) {
                        foreach ($this->em->getRepository(Municipality::class)->findBy(['comarca' => $comarca]) as $mun) {
                            $this->setMunicipalityValue($def, $indicator, $mun, (int) $year, $value, null);
                        }
                    }
                }
                $this->em->flush();
            }
        }

        // Municipality (overwrites backfill where direct data exists)
        if ($this->shouldImport(ImportScope::Municipality)) {
            foreach ($years as $year) {
                $url = str_replace('[[[year]]]', $year, $def->url);
                $data = $this->idescatTable->getValues($url);
                foreach ($data as $code => $row) {
                    $mun = $this->getMunicipalityByCode($code);
                    if (!$mun) {
                        continue;
                    }
                    $value = $this->calcWeightedAge($row);
                    if (null === $value) {
                        continue;
                    }
                    $this->setMunicipalityValue($def, $indicator, $mun, (int) $year, $value, null);
                }
                $this->em->flush();
                gc_collect_cycles();
            }
        }
    }

    // =========================================================================
    // import511 — 5.1.1 (gender unemployment ratio: women% vs men%)
    // =========================================================================

    private function import511(IndicatorDefinition $def, Indicator $indicator): void
    {
        $years = $this->resolveYears($def);

        // Province (geo=prov → iterate all provinces)
        if ($this->shouldImport(ImportScope::Province) && $def->urlProv) {
            foreach ($years as $year) {
                $url = str_replace('[[[year]]]', $year, $def->urlProv);
                $data = $this->idescatTable->getValues($url);
                $popsF = $this->idescatJson->getAffiliatesByYear((int) $year, 'F', 'PROV');
                $popsM = $this->idescatJson->getAffiliatesByYear((int) $year, 'M', 'PROV');
                foreach ($data as $code => $row) {
                    $prov = $this->geo->getProvinceByCode($code);
                    if (!$prov) {
                        continue;
                    }
                    [$value, $value2] = $this->calc511($row, $prov->getProvinceCode(), $popsF, $popsM);
                    if (null !== $value) {
                        $this->setProvinceValue($def, $indicator, $prov, (int) $year, $value, $value2);
                    }
                }
            }
            $this->em->flush();
        }

        // Comarca
        if ($this->shouldImport(ImportScope::Comarca) && $def->urlComarca) {
            foreach ($years as $year) {
                $url = str_replace('[[[year]]]', $year, $def->urlComarca);
                $data = $this->idescatTable->getValues($url);
                $popsF = $this->idescatJson->getAffiliatesByYear((int) $year, 'F', 'COM');
                $popsM = $this->idescatJson->getAffiliatesByYear((int) $year, 'M', 'COM');
                foreach ($data as $code => $row) {
                    $comarca = $this->geo->getComarcaByCode($code);
                    if (!$comarca) {
                        continue;
                    }
                    [$value, $value2] = $this->calc511($row, $comarca->getComarcaCode(), $popsF, $popsM);
                    if (null !== $value) {
                        $this->setComarcaValue($def, $indicator, $comarca, (int) $year, $value, $value2);
                    }
                }
                $this->em->flush();
            }
        }

        // Municipality
        if ($this->shouldImport(ImportScope::Municipality)) {
            foreach ($years as $year) {
                $url = str_replace('[[[year]]]', $year, $def->url);
                $data = $this->idescatTable->getValues($url);
                $popsF = $this->idescatJson->getAffiliatesByYear((int) $year, 'F', 'MUN');
                $popsM = $this->idescatJson->getAffiliatesByYear((int) $year, 'M', 'MUN');
                foreach ($data as $code => $row) {
                    $mun = $this->getMunicipalityByCode($code);
                    if (!$mun) {
                        continue;
                    }
                    [$value, $value2] = $this->calc511($row, $mun->getMunicipalityCode6(), $popsF, $popsM);
                    if (null !== $value) {
                        $this->setMunicipalityValue($def, $indicator, $mun, (int) $year, $value, $value2);
                    }
                }
                $this->em->flush();
                gc_collect_cycles();
            }
        }
    }

    // =========================================================================
    // importSeparately — 8.5.1, 8.5.2 (per-entity calls; hardcoded years)
    // =========================================================================

    private function importSeparately(IndicatorDefinition $def, Indicator $indicator): void
    {
        $extra = $def->extra;
        $ages = $extra['ages'];
        $valueBands = $extra['valueBands'];

        // Province
        if ($this->shouldImport(ImportScope::Province) && $def->urlProv) {
            $province = $this->geo->getProvince();
            if ($province) {
                foreach (self::SEPARATELY_YEARS as $year) {
                    $popYear = in_array($year, [2023, 2024]) ? 2022 : $year;
                    $pops = $this->idescatJson->getProvincePopulationByAges($popYear, $ages);
                    $url = str_replace('[[[year]]]', $year, $def->urlProv);
                    $data = $this->idescatTable->getValues($url);
                    if (empty($data)) {
                        continue;
                    }
                    $value = $this->sumBands($data, $valueBands);
                    $value2 = $pops[$province->getProvinceCode()] ?? null;
                    if (!$value2) {
                        continue;
                    }
                    $this->setProvinceValue($def, $indicator, $province, $year, $value, (float) $value2);
                }
                $this->em->flush();
            }
        }

        // Comarca
        if ($this->shouldImport(ImportScope::Comarca) && $def->urlComarca) {
            $comarques = $this->em->getRepository(Comarca::class)->findAll();
            foreach (self::SEPARATELY_YEARS as $year) {
                $popYear = in_array($year, [2023, 2024]) ? 2022 : $year;
                $pops = $this->idescatJson->getComarcaPopulationByAges($popYear, $ages);
                foreach ($comarques as $comarca) {
                    $comCode = $comarca->getComarcaCode();
                    $code2 = strlen($comCode) < 2 ? '0'.$comCode : $comCode;
                    $url = str_replace(['[[[comarca]]]', '[[[year]]]'], [$code2, $year], $def->urlComarca);
                    $data = $this->idescatTable->getValues($url);
                    if (empty($data)) {
                        continue;
                    }
                    $value = $this->sumBands($data, $valueBands);
                    $value2 = $pops[$comCode] ?? null;
                    if (!$value2) {
                        continue;
                    }
                    $this->setComarcaValue($def, $indicator, $comarca, $year, $value, (float) $value2);
                }
                $this->em->flush();
            }
        }

        // Municipality
        if ($this->shouldImport(ImportScope::Municipality)) {
            $municipalities = $this->em->getRepository(Municipality::class)->findAll();
            foreach (self::SEPARATELY_YEARS as $year) {
                $popYear = in_array($year, [2023, 2024]) ? 2022 : $year;
                $pops = $this->idescatJson->getMunicipalityPopulationByAges($popYear, $ages);
                foreach ($municipalities as $mun) {
                    $munCode6 = $mun->getMunicipalityCode6();
                    $url = str_replace(['[[[ine6]]]', '[[[year]]]'], [$munCode6, $year], $def->url);
                    $data = $this->idescatTable->getValues($url);
                    if (empty($data)) {
                        continue;
                    }
                    $value = $this->sumBands($data, $valueBands);
                    $value2 = $pops[$munCode6] ?? null;
                    if (!$value2) {
                        continue;
                    }
                    $this->setMunicipalityValue($def, $indicator, $mun, $year, $value, (float) $value2);
                }
                $this->em->flush();
                gc_collect_cycles();
            }
        }
    }

    // =========================================================================
    // import891 — 8.9.1 (5-URL tourism accumulation)
    // =========================================================================

    private function import891(IndicatorDefinition $def, Indicator $indicator): void
    {
        $years = $this->resolveYears($def);
        $extra = $def->extra;

        // Municipality
        if ($this->shouldImport(ImportScope::Municipality)) {
            $munUrls = array_merge([$def->url], $def->urls ?? []);
            foreach ($years as $year) {
                $pops = $this->do->getMunicipalityPopulationByYear((string) $year);
                $accumulated = $this->accumulateSsv($munUrls, $year);
                foreach ($accumulated as $code => $total) {
                    $mun = $this->getMunicipalityByCode($code);
                    if (!$mun) {
                        continue;
                    }
                    $pop = $pops[$mun->getMunicipalityCode6()] ?? null;
                    if (!$pop) {
                        continue;
                    }
                    $this->setMunicipalityValue($def, $indicator, $mun, (int) $year, $total, (float) $pop);
                }
                $this->em->flush();
                gc_collect_cycles();
            }
        }

        // Comarca
        if ($this->shouldImport(ImportScope::Comarca) && $def->urlComarca) {
            $comUrls = array_merge([$def->urlComarca], $extra['urlsComarca'] ?? []);
            foreach ($years as $year) {
                $pops = $this->do->getComarcaPopulationByYear((string) $year);
                $accumulated = $this->accumulateSsv($comUrls, $year);
                foreach ($accumulated as $code => $total) {
                    $comarca = $this->geo->getComarcaByCode($code);
                    if (!$comarca) {
                        continue;
                    }
                    $pop = $pops[$comarca->getComarcaCode()] ?? null;
                    if (!$pop) {
                        continue;
                    }
                    $this->setComarcaValue($def, $indicator, $comarca, (int) $year, $total, (float) $pop);
                }
                $this->em->flush();
            }
        }

        // Province
        if ($this->shouldImport(ImportScope::Province) && $def->urlProv) {
            $provUrls = array_merge([$def->urlProv], $extra['urlsProv'] ?? []);
            $province = $this->geo->getProvince();
            if ($province) {
                foreach ($years as $year) {
                    $pops = $this->do->getProvincePopulationByYear((string) $year);
                    $total = 0.0;
                    foreach ($provUrls as $urlTemplate) {
                        $url = str_replace('[[[year]]]', $year, $urlTemplate);
                        $data = $this->idescatTable->getValues($url);
                        foreach ($data as $code => $row) {
                            if (!$this->geo->getProvinceByCode($code)) {
                                continue;
                            }
                            $total += (float) ($row['Total'] ?? $row['Places'] ?? 0);
                        }
                    }
                    $pop = $pops[$province->getProvinceCode()] ?? null;
                    if (!$pop) {
                        continue;
                    }
                    $this->setProvinceValue($def, $indicator, $province, (int) $year, $total, (float) $pop);
                }
                $this->em->flush();
            }
        }
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    private function resolveYears(IndicatorDefinition $def): array
    {
        $years = $this->idescatTable->getYears($def->urlInfo);

        foreach ($def->urlsInfo ?? [] as $ui) {
            $years = array_values(array_intersect($years, $this->idescatTable->getYears($ui)));
        }

        if (!empty($def->extra['yearMin'])) {
            $years = array_values(array_filter($years, fn ($y) => (int) $y >= $def->extra['yearMin']));
        }

        return $years;
    }

    /** Returns population array indexed by geo code, or null when value2 comes from the same row. */
    private function getPopulations(IndicatorDefinition $def, int $year, string $scope): ?array
    {
        return match ($def->extra['popSource'] ?? null) {
            'do' => match ($scope) {
                'MUN' => $this->do->getMunicipalityPopulationByYear((string) $year),
                'COM' => $this->do->getComarcaPopulationByYear((string) $year),
                'PROV' => $this->do->getProvincePopulationByYear((string) $year),
            },
            'idescat_ages' => match ($scope) {
                'MUN' => $this->idescatJson->getMunicipalityPopulationByAges($year, $def->extra['popAges']),
                'COM' => $this->idescatJson->getComarcaPopulationByAges($year, $def->extra['popAges']),
                'PROV' => $this->idescatJson->getProvincePopulationByAges($year, $def->extra['popAges']),
            },
            'affiliates_f' => $this->idescatJson->getAffiliatesByYear($year, 'F', $scope),
            default => null,
        };
    }

    /** Extracts [value, value2] from an SSV row; returns [null, null] to signal skip. */
    private function extractSimpleValues(array $extra, array $row, string $geoCode, ?array $pops): array
    {
        $raw = $row[$extra['valueKey']] ?? null;
        if (null === $raw || !is_numeric($raw)) {
            return [null, null];
        }
        $value = (float) $raw;

        // value2 from same row (no population source)
        if (isset($extra['value2Key']) && !isset($extra['popSource'])) {
            $raw2 = $row[$extra['value2Key']] ?? null;

            return [$value, null !== $raw2 ? (float) $raw2 : null];
        }

        // value2 from population lookup
        if (null !== $pops) {
            $value2 = $pops[$geoCode] ?? null;
            if (!$value2) {
                return [null, null];
            }

            return [$value, (float) $value2];
        }

        return [$value, null];
    }

    /** Computes women% and men% unemployment rates from an SSV row and affiliate populations. */
    private function calc511(array $row, string $geoCode, array $popsF, array $popsM): array
    {
        $valueD = EtlUtils::toFloat($row['Sexe. Dones'] ?? '');
        $valueH = EtlUtils::toFloat($row['Sexe. Homes'] ?? '');

        if (!is_numeric($valueD) || !is_numeric($valueH)) {
            return [null, null];
        }

        $popD = $popsF[$geoCode] ?? null;
        $popH = $popsM[$geoCode] ?? null;
        if (!$popD || !$popH) {
            return [null, null];
        }

        return [($valueD / $popD) * 100, ($valueH / $popH) * 100];
    }

    /** Computes the weighted average death age from an age-column SSV row. */
    private function calcWeightedAge(array $row): ?float
    {
        $total = 0.0;
        $totalPeople = 0.0;
        foreach ($row as $key => $rawVal) {
            if (in_array($key, ['Total', 'No hi consta', 'Codi', 'Nom'], true)) {
                continue;
            }
            $age = (int) explode(' ', $key)[0];
            $count = EtlUtils::toFloat($rawVal);
            $total += $age * $count;
            $totalPeople += $count;
        }

        return $totalPeople > 0 ? $total / $totalPeople : null;
    }

    /** Sums specific age-band rows from a per-entity SSV result (keyed by band label). */
    private function sumBands(array $data, array $bands): float
    {
        $total = 0.0;
        foreach ($bands as $band) {
            $total += EtlUtils::toFloat($data[$band]['Sexe. Total'] ?? 0);
        }

        return $total;
    }

    /** Accumulates 'Total'/'Places' from multiple SSV URLs for the same year, keyed by geo code. */
    private function accumulateSsv(array $urlTemplates, string|int $year): array
    {
        $accumulated = [];
        foreach ($urlTemplates as $urlTemplate) {
            $url = str_replace('[[[year]]]', $year, $urlTemplate);
            $data = $this->idescatTable->getValues($url);
            foreach ($data as $code => $row) {
                $v = (float) ($row['Total'] ?? $row['Places'] ?? 0);
                $accumulated[$code] = ($accumulated[$code] ?? 0.0) + $v;
            }
        }

        return $accumulated;
    }
}
