<?php

namespace App\Service\Etl\Importer;

use App\Entity\Indicator;
use App\Service\Etl\Dto\EtlContext;
use App\Service\Etl\Dto\IndicatorDefinition;
use App\Service\Etl\Enum\ImportScope;
use App\Service\Etl\Util\EtlUtils;

/**
 * Migrates indicators backed by the IDESCAT JSON API (api.idescat.cat/taules/v2/).
 *
 * Indicators:
 *   importAllYears   — 8.2.1, 10.1.4 (all years in one request; no year in URL)
 *   import341        — 3.4.1 (three URL sets for different year ranges; AGE dimension)
 *   importEducation  — 4.4.1, 4.4.2, 4.4.3, 4.4.4 (LEV_EDU_ATT dimension; per-year)
 *   importByYear     — 8.3.3, 9.2.1, 4.5.1 (per-year; various inner dimensions)
 */
final class IdescatImporter extends AbstractEtlImporter
{
    private const MUN_FILTER = 'mun='.EtlUtils::BCN_MUNICIPALITY_FILTER;

    // Includes code 43 (Moianès), which the canonical BCN_COMARCA_FILTER omits — keep
    // the local override until the team confirms whether 43 belongs to the canonical set.
    private const COM_FILTER = 'com=40,11,06,07,03,24,21,14,41,42,13,17,34,43';
    private const PROV_FILTER = 'prov=08';

    // Age codes counted as ≥85 years old in the newer censph/539 dataset (2023+)
    private const AGE_GE85_NEW = ['Y085_089', 'Y090_094', 'Y095_099', 'Y_GE100'];

    // Age codes counted as ≥85 in the older pmh/1180 datasets (pre-2023)
    private const AGE_GE85_OLD = [
        'Y085', 'Y086', 'Y087', 'Y088', 'Y089',
        'Y090', 'Y091', 'Y092', 'Y093', 'Y094',
        'Y095', 'Y096', 'Y097', 'Y098', 'Y099',
        'Y_GE100',
    ];

    protected function getDefinitions(): array
    {
        return [
            // ----------------------------------------------------------------
            // 3.4.1 — Índex de Sobreenvelliment (three URL sets for year ranges)
            // ----------------------------------------------------------------
            '3.4.1' => new IndicatorDefinition(
                indicatorId: '3.4.1',
                targetId: '3.4',
                targetName: 'Incrementar programes de prevenció i tractament de malalties no transmissibles adreçades a la reducció de la mortalitat prematura i promoure la salut mental i el benestar.',
                sdg: 3,
                indicatorName: 'Índex de Sobreenvelliment',
                indicatorDescription: 'Relació entre la població de 85 anys i més amb la població de 65 i més.',
                sign: true,
                source: 'IDESCAT',
                unit: 'POP',
                scale: 1,
                // 2023+ dataset
                url: 'https://api.idescat.cat/taules/v2/censph/539/5976/mun/data?sex=TOTAL&age=Y065_069,Y070_074,Y075_079,Y080_084,Y085_089,Y090_094,Y095_099,Y_GE100&year=[[[year]]]&'.self::MUN_FILTER,
                urlInfo: 'https://api.idescat.cat/taules/v2/censph/539/5976/mun/?sex=TOTAL&age=Y065_069,Y070_074,Y075_079,Y080_084,Y085_089,Y090_094,Y095_099,Y_GE100&'.self::MUN_FILTER,
                urlComarca: 'https://api.idescat.cat/taules/v2/censph/539/5976/com/data?sex=TOTAL&age=Y065_069,Y070_074,Y075_079,Y080_084,Y085_089,Y090_094,Y095_099,Y_GE100&year=[[[year]]]&'.self::COM_FILTER,
                urlProv: 'https://api.idescat.cat/taules/v2/censph/539/5976/prov/data?sex=TOTAL&age=Y065_069,Y070_074,Y075_079,Y080_084,Y085_089,Y090_094,Y095_099,Y_GE100&year=[[[year]]]&'.self::PROV_FILTER,
                extra: [
                    'yearMin' => 2023,
                    // 2014–2022 dataset
                    'url1' => 'https://api.idescat.cat/taules/v2/pmh/1180/8078/mun/data?sex=TOTAL&age=Y065,Y066,Y067,Y068,Y069,Y070,Y071,Y072,Y073,Y074,Y075,Y076,Y077,Y078,Y079,Y080,Y081,Y082,Y083,Y084,Y085,Y086,Y087,Y088,Y089,Y090,Y091,Y092,Y093,Y094,Y095,Y096,Y097,Y098,Y099,Y_GE100&year=[[[year]]]&'.self::MUN_FILTER,
                    'urlInfo1' => 'https://api.idescat.cat/taules/v2/pmh/1180/8078/mun/?sex=TOTAL&age=Y065,Y066,Y067,Y068,Y069,Y070,Y071,Y072,Y073,Y074,Y075,Y076,Y077,Y078,Y079,Y080,Y081,Y082,Y083,Y084,Y085,Y086,Y087,Y088,Y089,Y090,Y091,Y092,Y093,Y094,Y095,Y096,Y097,Y098,Y099,Y_GE100&'.self::MUN_FILTER,
                    'urlComarca1' => 'https://api.idescat.cat/taules/v2/pmh/1180/8078/com/data?sex=TOTAL&age=Y065,Y066,Y067,Y068,Y069,Y070,Y071,Y072,Y073,Y074,Y075,Y076,Y077,Y078,Y079,Y080,Y081,Y082,Y083,Y084,Y085,Y086,Y087,Y088,Y089,Y090,Y091,Y092,Y093,Y094,Y095,Y096,Y097,Y098,Y099,Y_GE100&year=[[[year]]]&'.self::COM_FILTER,
                    'urlProv1' => 'https://api.idescat.cat/taules/v2/pmh/1180/8078/prov/data?sex=TOTAL&age=Y065,Y066,Y067,Y068,Y069,Y070,Y071,Y072,Y073,Y074,Y075,Y076,Y077,Y078,Y079,Y080,Y081,Y082,Y083,Y084,Y085,Y086,Y087,Y088,Y089,Y090,Y091,Y092,Y093,Y094,Y095,Y096,Y097,Y098,Y099,Y_GE100&year=[[[year]]]&'.self::PROV_FILTER,
                    'yearMax1' => 2022,
                    // pre-2014 dataset
                    'url2' => 'https://api.idescat.cat/taules/v2/pmh/1180/1063/mun/data?sex=TOTAL&age=Y065,Y066,Y067,Y068,Y069,Y070,Y071,Y072,Y073,Y074,Y075,Y076,Y077,Y078,Y079,Y080,Y081,Y082,Y083,Y084,Y085,Y086,Y087,Y088,Y089,Y090,Y091,Y092,Y093,Y094,Y095,Y096,Y097,Y098,Y099,Y_GE100&year=[[[year]]]&'.self::MUN_FILTER,
                    'urlInfo2' => 'https://api.idescat.cat/taules/v2/pmh/1180/1063/mun/?sex=TOTAL&age=Y065,Y066,Y067,Y068,Y069,Y070,Y071,Y072,Y073,Y074,Y075,Y076,Y077,Y078,Y079,Y080,Y081,Y082,Y083,Y084,Y085,Y086,Y087,Y088,Y089,Y090,Y091,Y092,Y093,Y094,Y095,Y096,Y097,Y098,Y099,Y_GE100&'.self::MUN_FILTER,
                    'urlComarca2' => 'https://api.idescat.cat/taules/v2/pmh/1180/1063/com/data?sex=TOTAL&age=Y065,Y066,Y067,Y068,Y069,Y070,Y071,Y072,Y073,Y074,Y075,Y076,Y077,Y078,Y079,Y080,Y081,Y082,Y083,Y084,Y085,Y086,Y087,Y088,Y089,Y090,Y091,Y092,Y093,Y094,Y095,Y096,Y097,Y098,Y099,Y_GE100&year=[[[year]]]&'.self::COM_FILTER,
                    'urlProv2' => 'https://api.idescat.cat/taules/v2/pmh/1180/1063/prov/data?sex=TOTAL&age=Y065,Y066,Y067,Y068,Y069,Y070,Y071,Y072,Y073,Y074,Y075,Y076,Y077,Y078,Y079,Y080,Y081,Y082,Y083,Y084,Y085,Y086,Y087,Y088,Y089,Y090,Y091,Y092,Y093,Y094,Y095,Y096,Y097,Y098,Y099,Y_GE100&year=[[[year]]]&'.self::PROV_FILTER,
                ],
            ),

            // ----------------------------------------------------------------
            // 4.4.1–4.4.4 — Formació (share same URL, different edu level)
            // ----------------------------------------------------------------
            '4.4.1' => new IndicatorDefinition(
                indicatorId: '4.4.1',
                targetId: '4.4',
                targetName: "Augmentar substancialment el nombre de joves i persones adultes perquè tinguin les competències necessàries per accedir al mercat de treball, a un lloc de treball digne i a l'emprenedoria, en condicions d'igualtat.",
                sdg: 4,
                indicatorName: 'Formació (Educació primària o inferior)',
                indicatorDescription: 'Nivell de formació assolit (Educació primària o inferior)',
                sign: true,
                source: 'IDESCAT',
                unit: 'PERCENT',
                scale: 1,
                url: 'https://api.idescat.cat/taules/v2/censph/7104/20170/mun/data?year=[[[year]]]&'.self::MUN_FILTER,
                urlInfo: 'https://api.idescat.cat/taules/v2/censph/7104/20170/mun/?'.self::MUN_FILTER,
                urlComarca: 'https://api.idescat.cat/taules/v2/censph/7104/20170/com/data?year=[[[year]]]&'.self::COM_FILTER,
                urlProv: 'https://api.idescat.cat/taules/v2/censph/7104/20170/prov/data?year=[[[year]]]&'.self::PROV_FILTER,
                extra: ['eduLevel' => 'ED_0-1'],
            ),
            '4.4.2' => new IndicatorDefinition(
                indicatorId: '4.4.2',
                targetId: '4.4',
                targetName: "Augmentar substancialment el nombre de joves i persones adultes perquè tinguin les competències necessàries per accedir al mercat de treball, a un lloc de treball digne i a l'emprenedoria, en condicions d'igualtat.",
                sdg: 4,
                indicatorName: "Formació (Primera etapa d'educació secundària i similar)",
                indicatorDescription: "Nivell de formació assolit (Primera etapa d'educació secundària i similar)",
                sign: true,
                source: 'IDESCAT',
                unit: 'PERCENT',
                scale: 1,
                url: 'https://api.idescat.cat/taules/v2/censph/7104/20170/mun/data?year=[[[year]]]&'.self::MUN_FILTER,
                urlInfo: 'https://api.idescat.cat/taules/v2/censph/7104/20170/mun/?'.self::MUN_FILTER,
                urlComarca: 'https://api.idescat.cat/taules/v2/censph/7104/20170/com/data?year=[[[year]]]&'.self::COM_FILTER,
                urlProv: 'https://api.idescat.cat/taules/v2/censph/7104/20170/prov/data?year=[[[year]]]&'.self::PROV_FILTER,
                extra: ['eduLevel' => 'ED_21-24'],
            ),
            '4.4.3' => new IndicatorDefinition(
                indicatorId: '4.4.3',
                targetId: '4.4',
                targetName: "Augmentar substancialment el nombre de joves i persones adultes perquè tinguin les competències necessàries per accedir al mercat de treball, a un lloc de treball digne i a l'emprenedoria, en condicions d'igualtat.",
                sdg: 4,
                indicatorName: "Formació (Segona etapa d'educació secundària i similar)",
                indicatorDescription: "Nivell de formació assolit (Segona etapa d'educació secundària i similar)",
                sign: true,
                source: 'IDESCAT',
                unit: 'PERCENT',
                scale: 1,
                url: 'https://api.idescat.cat/taules/v2/censph/7104/20170/mun/data?year=[[[year]]]&'.self::MUN_FILTER,
                urlInfo: 'https://api.idescat.cat/taules/v2/censph/7104/20170/mun/?'.self::MUN_FILTER,
                urlComarca: 'https://api.idescat.cat/taules/v2/censph/7104/20170/com/data?year=[[[year]]]&'.self::COM_FILTER,
                urlProv: 'https://api.idescat.cat/taules/v2/censph/7104/20170/prov/data?year=[[[year]]]&'.self::PROV_FILTER,
                extra: ['eduLevel' => 'ED_3_4'],
            ),
            '4.4.4' => new IndicatorDefinition(
                indicatorId: '4.4.4',
                targetId: '4.4',
                targetName: "Augmentar substancialment el nombre de joves i persones adultes perquè tinguin les competències necessàries per accedir al mercat de treball, a un lloc de treball digne i a l'emprenedoria, en condicions d'igualtat.",
                sdg: 4,
                indicatorName: 'Formació (Educació superior)',
                indicatorDescription: 'Nivell de formació assolit (Educació superior)',
                sign: true,
                source: 'IDESCAT',
                unit: 'PERCENT',
                scale: 1,
                url: 'https://api.idescat.cat/taules/v2/censph/7104/20170/mun/data?year=[[[year]]]&'.self::MUN_FILTER,
                urlInfo: 'https://api.idescat.cat/taules/v2/censph/7104/20170/mun/?'.self::MUN_FILTER,
                urlComarca: 'https://api.idescat.cat/taules/v2/censph/7104/20170/com/data?year=[[[year]]]&'.self::COM_FILTER,
                urlProv: 'https://api.idescat.cat/taules/v2/censph/7104/20170/prov/data?year=[[[year]]]&'.self::PROV_FILTER,
                extra: ['eduLevel' => 'ED5-8'],
            ),

            // ----------------------------------------------------------------
            // 4.5.1 — Índex de paritat en activitats educatives
            // ----------------------------------------------------------------
            '4.5.1' => new IndicatorDefinition(
                indicatorId: '4.5.1',
                targetId: '4.5',
                targetName: "Eliminar les disparitats de gènere en l'educació i garantir l'accés en condicions d'igualtat a tots els col·lectius vulnerables.",
                sdg: 4,
                indicatorName: 'Índex de paritat en activitats educatives',
                indicatorDescription: 'Índex de paritat entre dones i homes de la població de més de 15 anys que han realitzat activitats educatives en els darrers 12 mesos.',
                sign: true,
                source: 'IDESCAT',
                unit: 'PERCENT',
                scale: 1,
                url: 'https://api.idescat.cat/taules/v2/censph/16690/20161/mun/data?year=[[[year]]]&EDU_LEV_CS=ED_NE,TOTAL&SEX=F,M&'.self::MUN_FILTER,
                urlInfo: 'https://api.idescat.cat/taules/v2/censph/16690/20161/mun/?EDU_LEV_CS=ED_NE,TOTAL&SEX=F,M&'.self::MUN_FILTER,
                urlComarca: 'https://api.idescat.cat/taules/v2/censph/16690/20161/com/data?year=[[[year]]]&EDU_LEV_CS=ED_NE,TOTAL&SEX=F,M&'.self::COM_FILTER,
                urlProv: 'https://api.idescat.cat/taules/v2/censph/16690/20161/prov/data?year=[[[year]]]&EDU_LEV_CS=ED_NE,TOTAL&SEX=F,M&'.self::PROV_FILTER,
            ),

            // ----------------------------------------------------------------
            // 8.2.1 — PIB per habitant (all years in one request)
            // ----------------------------------------------------------------
            '8.2.1' => new IndicatorDefinition(
                indicatorId: '8.2.1',
                targetId: '8.2',
                targetName: 'Augmentar de forma generalitzada la productivitat econòmica del conjunt del territori mitjançant la diversificació, la innovació, la planificació estratègica i la concertació territorial.',
                sdg: 8,
                indicatorName: 'PIB per habitant',
                indicatorDescription: "El producte interior brut a preus de mercat (PIB pm) mesura el resultat final de l'activitat econòmica de les unitats productores en el territori.",
                sign: true,
                source: 'IDESCAT',
                unit: 'percent',
                scale: 1000,
                url: 'https://api.idescat.cat/taules/v2/pibc/13830/14779/mun/data?concept=GDP_E_INH&'.self::MUN_FILTER,
                urlComarca: 'https://api.idescat.cat/taules/v2/pibc/13830/14779/com/data?concept=GDP_E_INH&'.self::COM_FILTER,
                urlProv: 'https://api.idescat.cat/taules/v2/pibc/13830/14779/com/data?concept=GDP_ME&'.self::COM_FILTER,
                extra: ['provMultiplicator' => 1_000_000],
            ),

            // ----------------------------------------------------------------
            // 8.3.3 — Taxa d'atur
            // ----------------------------------------------------------------
            '8.3.3' => new IndicatorDefinition(
                indicatorId: '8.3.3',
                targetId: '8.3',
                targetName: "Promoure la creació d'ocupació digna a través de la innovació, creativitat i emprenedoria, fomentant el creixement de les petites i mitjanes empreses.",
                sdg: 8,
                indicatorName: "Taxa d'atur",
                indicatorDescription: 'Proporció de població activa de 16 anys o més que està aturada.',
                sign: true,
                source: 'IDESCAT',
                unit: 'percent',
                scale: 1,
                url: 'https://api.idescat.cat/taules/v2/censph/16720/20208/mun/data?CONCEPT=PP_APY16OU&SEX=TOTAL&year=[[[year]]]&'.self::MUN_FILTER,
                urlInfo: 'https://api.idescat.cat/taules/v2/censph/16720/20208/mun/?CONCEPT=PP_APY16OU&SEX=TOTAL&'.self::MUN_FILTER,
                urlComarca: 'https://api.idescat.cat/taules/v2/censph/16720/20208/com/data?CONCEPT=PP_APY16OU&SEX=TOTAL&year=[[[year]]]&'.self::COM_FILTER,
                urlProv: 'https://api.idescat.cat/taules/v2/censph/16720/20208/prov/data?CONCEPT=PP_APY16OU&SEX=TOTAL&year=[[[year]]]&'.self::PROV_FILTER,
            ),

            // ----------------------------------------------------------------
            // 9.2.1 — Ocupació al sector industrial (monthly data)
            // ----------------------------------------------------------------
            '9.2.1' => new IndicatorDefinition(
                indicatorId: '9.2.1',
                targetId: '9.2',
                targetName: "Reforçar les polítiques de suport al teixit productiu per aconseguir una industrialització inclusiva i sostenible, augmentant la contribució de la indústria a l'ocupació local i al PIB del conjunt del territori.",
                sdg: 9,
                indicatorName: 'Ocupació al sector industrial',
                indicatorDescription: '% població ocupada en el sector industrial',
                sign: true,
                source: 'IDESCAT',
                unit: 'POP',
                scale: 1,
                url: 'https://api.idescat.cat/taules/v2/afi/10602/11111/mun/data?SEC_ACTIV_G=B-E,TOTAL&month=[[[year]]]&'.self::MUN_FILTER,
                urlInfo: 'https://api.idescat.cat/taules/v2/afi/10602/11111/mun/?SEC_ACTIV_G=B-E,TOTAL&'.self::MUN_FILTER,
                urlComarca: 'https://api.idescat.cat/taules/v2/afi/10602/11111/com/data?SEC_ACTIV_G=B-E,TOTAL&month=[[[year]]]&'.self::COM_FILTER,
                urlProv: 'https://api.idescat.cat/taules/v2/afi/10602/11111/prov/data?SEC_ACTIV_G=B-E,TOTAL&month=[[[year]]]&'.self::PROV_FILTER,
            ),

            // ----------------------------------------------------------------
            // 10.1.4 — Renda per habitant (all years in one request)
            // ----------------------------------------------------------------
            '10.1.4' => new IndicatorDefinition(
                indicatorId: '10.1.4',
                targetId: '10.1',
                targetName: 'Aconseguir progressivament un creixement dels ingressos del 40% més pobre de la població del territori a una taxa superior a la mitjana nacional.',
                sdg: 10,
                indicatorName: 'Renda per habitant',
                indicatorDescription: 'Relació entre el total de la renda del municipi i/o àmbit seleccionat vers la població total del municipi.',
                sign: true,
                source: 'IDESCAT',
                unit: 'PER_CAPITA_EUR',
                scale: 1,
                url: 'https://api.idescat.cat/taules/v2/rfdbc/13301/14148/mun/data?indicator=PER_CAPITA_EUR&'.self::MUN_FILTER,
                urlComarca: 'https://api.idescat.cat/taules/v2/rfdbc/13301/14148/com/data?indicator=PER_CAPITA_EUR&'.self::COM_FILTER,
                urlProv: 'https://api.idescat.cat/taules/v2/rfdbc/13301/14148/com/data?indicator=VALUE_EK&'.self::COM_FILTER,
                extra: ['provMultiplicator' => 1_000],
            ),
        ];
    }

    // =========================================================================
    // Dispatch
    // =========================================================================

    protected function import(IndicatorDefinition $def, Indicator $indicator, EtlContext $context): void
    {
        match ($def->indicatorId) {
            '8.2.1', '10.1.4' => $this->importAllYears($def, $indicator),
            '3.4.1' => $this->import341($def, $indicator),
            '4.4.1', '4.4.2', '4.4.3', '4.4.4' => $this->importEducation($def, $indicator),
            default => $this->importByYear($def, $indicator),
        };
    }

    // =========================================================================
    // importAllYears — 8.2.1, 10.1.4
    // All years returned in one API call; no [[[year]]] placeholder in URL.
    // Province value = sum(comarca values × multiplicator) / province population.
    // =========================================================================

    private function importAllYears(IndicatorDefinition $def, Indicator $indicator): void
    {
        $multiplicator = (int) ($def->extra['provMultiplicator'] ?? 1);

        // Municipality
        if ($this->shouldImport(ImportScope::Municipality) && $def->url) {
            $data = $this->fetch($def->url);
            $years = $data['dimension']['YEAR']['category']['index'];
            $munis = $data['dimension']['MUN']['category']['index'];
            $values = $data['value'];
            $i = 0;
            foreach ($years as $year) {
                foreach ($munis as $muni) {
                    $value = $values[$i++];
                    if (null === $value) {
                        continue;
                    }
                    $mun = $this->getMunicipalityByCode($muni);
                    if (!$mun) {
                        continue;
                    }
                    $this->setMunicipalityValue($def, $indicator, $mun, (int) $year, (float) $value);
                }
            }
            $this->em->flush();
        }

        // Comarca
        if ($this->shouldImport(ImportScope::Comarca) && $def->urlComarca) {
            $data = $this->fetch($def->urlComarca);
            $years = $data['dimension']['YEAR']['category']['index'];
            $coms = $data['dimension']['COM']['category']['index'];
            $values = $data['value'];
            $i = 0;
            foreach ($years as $year) {
                foreach ($coms as $comCode) {
                    $value = $values[$i++];
                    if (null === $value) {
                        continue;
                    }
                    $comarca = $this->geo->getComarcaByCode($this->stripLeadingZero($comCode));
                    if (!$comarca) {
                        continue;
                    }
                    $this->setComarcaValue($def, $indicator, $comarca, (int) $year, (float) $value);
                }
            }
            $this->em->flush();
        }

        // Province — urlProv carries COM-level total values; we sum × multiplicator / prov population
        if ($this->shouldImport(ImportScope::Province) && $def->urlProv) {
            $province = $this->geo->getProvince();
            if ($province) {
                $data = $this->fetch($def->urlProv);
                $years = $data['dimension']['YEAR']['category']['index'];
                $coms = $data['dimension']['COM']['category']['index'];
                $values = $data['value'];
                $i = 0;
                foreach ($years as $year) {
                    $provPop = $this->do->getProvincePopulationByYear((string) $year)[8] ?? null;
                    $sum = 0.0;
                    foreach ($coms as $comCode) {
                        $raw = $values[$i++];
                        if (null !== $raw) {
                            $sum += (float) $raw * $multiplicator;
                        }
                    }
                    if (!$provPop) {
                        continue;
                    }
                    $this->setProvinceValue($def, $indicator, $province, (int) $year, $sum / (float) $provPop);
                }
                $this->em->flush();
            }
        }
    }

    // =========================================================================
    // import341 — 3.4.1 (three URL sets for year ranges)
    // value = population ≥85; value2 = population ≥65 (all requested ages).
    // =========================================================================

    private function import341(IndicatorDefinition $def, Indicator $indicator): void
    {
        $extra = $def->extra;

        // 2023+ (new dataset: grouped age bands)
        $years = array_filter(
            $this->idescatJson->getYears($def->urlInfo),
            fn ($y) => (int) $y >= (int) ($extra['yearMin'] ?? 2023)
        );
        foreach ($years as $year) {
            $this->import341Year($def, $indicator, $year, $def->url, $def->urlComarca, $def->urlProv, self::AGE_GE85_NEW);
        }

        // 2014–2022 (old dataset: individual age years)
        if (isset($extra['urlInfo1'])) {
            $years1 = array_filter(
                $this->idescatJson->getYears($extra['urlInfo1']),
                fn ($y) => (int) $y <= (int) ($extra['yearMax1'] ?? 2022)
            );
            foreach ($years1 as $year) {
                $this->import341Year($def, $indicator, $year, $extra['url1'], $extra['urlComarca1'], $extra['urlProv1'], self::AGE_GE85_OLD);
            }
        }

        // pre-2014 (older dataset)
        if (isset($extra['urlInfo2'])) {
            foreach ($this->idescatJson->getYears($extra['urlInfo2']) as $year) {
                $this->import341Year($def, $indicator, $year, $extra['url2'], $extra['urlComarca2'], $extra['urlProv2'], self::AGE_GE85_OLD);
            }
        }
    }

    private function import341Year(
        IndicatorDefinition $def,
        Indicator $indicator,
        string $year,
        string $urlMun,
        string $urlCom,
        string $urlProv,
        array $ge85Ages,
    ): void {
        $yr = (int) substr($year, 0, 4);

        if ($this->shouldImport(ImportScope::Municipality)) {
            foreach ($this->parse341($this->fetchYear($urlMun, $year), 'MUN', $ge85Ages) as $code => [$v, $v2]) {
                $mun = $this->getMunicipalityByCode($code);
                if ($mun) {
                    $this->setMunicipalityValue($def, $indicator, $mun, $yr, $v, $v2);
                }
            }
            $this->em->flush();
        }

        if ($this->shouldImport(ImportScope::Comarca)) {
            foreach ($this->parse341($this->fetchYear($urlCom, $year), 'COM', $ge85Ages) as $code => [$v, $v2]) {
                $comarca = $this->geo->getComarcaByCode($this->stripLeadingZero($code));
                if ($comarca) {
                    $this->setComarcaValue($def, $indicator, $comarca, $yr, $v, $v2);
                }
            }
            $this->em->flush();
        }

        if ($this->shouldImport(ImportScope::Province)) {
            $province = $this->geo->getProvince();
            if ($province) {
                foreach ($this->parse341($this->fetchYear($urlProv, $year), 'PROV', $ge85Ages) as [$v, $v2]) {
                    $this->setProvinceValue($def, $indicator, $province, $yr, $v, $v2);
                }
                $this->em->flush();
            }
        }
    }

    /** Returns [geoCode => [value≥85, value≥65]] for each geo entity in the response. */
    private function parse341(array $data, string $geoKey, array $ge85Ages): array
    {
        $geos = $data['dimension'][$geoKey]['category']['index'];
        $ages = $data['dimension']['AGE']['category']['index'];
        $values = $data['value'];
        $result = [];
        $i = 0;

        foreach ($geos as $code) {
            $ge85 = 0.0;
            $total = 0.0;
            foreach ($ages as $age) {
                $v = $values[$i++] ?? 0;
                if (null === $v) {
                    continue;
                }
                $total += (float) $v;
                if (in_array($age, $ge85Ages, true)) {
                    $ge85 += (float) $v;
                }
            }
            if ($total > 0) {
                $result[$code] = [$ge85, $total];
            }
        }

        return $result;
    }

    // =========================================================================
    // importEducation — 4.4.1, 4.4.2, 4.4.3, 4.4.4
    // All four indicators share the same URL; each picks one LEV_EDU_ATT level.
    // value = count for the chosen education level; value2 = TOTAL count.
    // =========================================================================

    private function importEducation(IndicatorDefinition $def, Indicator $indicator): void
    {
        $eduLevel = $def->extra['eduLevel'];
        $years = $this->idescatJson->getYears($def->urlInfo);

        foreach ($years as $year) {
            $yr = (int) substr($year, 0, 4);

            if ($this->shouldImport(ImportScope::Municipality)) {
                foreach ($this->parseEducation($this->fetchYear($def->url, $year), 'MUN', $eduLevel) as $code => [$v, $v2]) {
                    $mun = $this->getMunicipalityByCode($code);
                    if ($mun) {
                        $this->setMunicipalityValue($def, $indicator, $mun, $yr, $v, $v2);
                    }
                }
                $this->em->flush();
            }

            if ($this->shouldImport(ImportScope::Comarca) && $def->urlComarca) {
                foreach ($this->parseEducation($this->fetchYear($def->urlComarca, $year), 'COM', $eduLevel) as $code => [$v, $v2]) {
                    $comarca = $this->geo->getComarcaByCode($this->stripLeadingZero($code));
                    if ($comarca) {
                        $this->setComarcaValue($def, $indicator, $comarca, $yr, $v, $v2);
                    }
                }
                $this->em->flush();
            }

            if ($this->shouldImport(ImportScope::Province) && $def->urlProv) {
                $province = $this->geo->getProvince();
                if ($province) {
                    foreach ($this->parseEducation($this->fetchYear($def->urlProv, $year), 'PROV', $eduLevel) as [$v, $v2]) {
                        $this->setProvinceValue($def, $indicator, $province, $yr, $v, $v2);
                    }
                    $this->em->flush();
                }
            }
        }
    }

    /** Returns [geoCode => [chosenLevelCount, totalCount]] */
    private function parseEducation(array $data, string $geoKey, string $eduLevel): array
    {
        $geos = $data['dimension'][$geoKey]['category']['index'];
        $levels = $data['dimension']['LEV_EDU_ATT']['category']['index'];
        $values = $data['value'];
        $result = [];
        $i = 0;

        foreach ($geos as $code) {
            $chosen = null;
            $total = null;
            foreach ($levels as $level) {
                $v = $values[$i++] ?? null;
                if ($level === $eduLevel) {
                    $chosen = $v;
                }
                if ('TOTAL' === $level) {
                    $total = $v;
                }
            }
            if (null !== $total && (float) $total > 0 && null !== $chosen) {
                $result[$code] = [(float) $chosen, (float) $total];
            }
        }

        return $result;
    }

    // =========================================================================
    // importByYear — 8.3.3, 9.2.1, 4.5.1
    // Per-year API calls; dimension handling differs per indicator.
    // =========================================================================

    private function importByYear(IndicatorDefinition $def, Indicator $indicator): void
    {
        $years = $this->idescatJson->getYears($def->urlInfo);

        foreach ($years as $year) {
            $yr = (int) substr($year, 0, 4);

            if ($this->shouldImport(ImportScope::Municipality)) {
                foreach ($this->parseDimensions($def, $this->fetchYear($def->url, $year), 'MUN') as $code => [$v, $v2]) {
                    $mun = $this->getMunicipalityByCode($code);
                    if ($mun) {
                        $this->setMunicipalityValue($def, $indicator, $mun, $yr, $v, $v2);
                    }
                }
                $this->em->flush();
            }

            if ($this->shouldImport(ImportScope::Comarca) && $def->urlComarca) {
                foreach ($this->parseDimensions($def, $this->fetchYear($def->urlComarca, $year), 'COM') as $code => [$v, $v2]) {
                    $comarca = $this->geo->getComarcaByCode($this->stripLeadingZero($code));
                    if ($comarca) {
                        $this->setComarcaValue($def, $indicator, $comarca, $yr, $v, $v2);
                    }
                }
                $this->em->flush();
            }

            if ($this->shouldImport(ImportScope::Province) && $def->urlProv) {
                $province = $this->geo->getProvince();
                if ($province) {
                    foreach ($this->parseDimensions($def, $this->fetchYear($def->urlProv, $year), 'PROV') as [$v, $v2]) {
                        $this->setProvinceValue($def, $indicator, $province, $yr, $v, $v2);
                    }
                    $this->em->flush();
                }
            }
        }
    }

    /**
     * Per-indicator dimension parsing for importByYear.
     * Returns [geoCode => [value, value2]].
     */
    private function parseDimensions(IndicatorDefinition $def, array $data, string $geoKey): array
    {
        return match ($def->indicatorId) {
            '8.3.3' => $this->parse833($data, $geoKey),
            '9.2.1' => $this->parse921($data, $geoKey),
            '4.5.1' => $this->parse451($data, $geoKey),
            default => [],
        };
    }

    /** 8.3.3: MUN × SEX(=TOTAL) → value = rate, value2 = null */
    private function parse833(array $data, string $geoKey): array
    {
        $geos = $data['dimension'][$geoKey]['category']['index'];
        $sexes = $data['dimension']['SEX']['category']['index'];
        $values = $data['value'];
        $result = [];
        $i = 0;

        foreach ($geos as $code) {
            $v = null;
            foreach ($sexes as $_) {
                $v = $values[$i++] ?? null;
            }
            if (null !== $v) {
                $result[$code] = [(float) $v, null];
            }
        }

        return $result;
    }

    /** 9.2.1: MUN × SEC_ACTIV_G × CON_REG_SS → value = industry affiliates, value2 = total affiliates */
    private function parse921(array $data, string $geoKey): array
    {
        $geos = $data['dimension'][$geoKey]['category']['index'];
        $secs = $data['dimension']['SEC_ACTIV_G']['category']['index'];
        $regims = $data['dimension']['CON_REG_SS']['category']['index'];
        $values = $data['value'];
        $result = [];
        $i = 0;

        foreach ($geos as $code) {
            $industry = 0.0;
            $total = 0.0;
            foreach ($secs as $sec) {
                foreach ($regims as $_) {
                    $v = $values[$i++] ?? null;
                    if (null === $v) {
                        continue;
                    }
                    if ('B-E' === $sec) {
                        $industry += (float) $v;
                    }
                    if ('TOTAL' === $sec) {
                        $total += (float) $v;
                    }
                }
            }
            if ($total > 0) {
                $result[$code] = [$industry, $total];
            }
        }

        return $result;
    }

    /**
     * 4.5.1: MUN × SEX(F,M) × EDU_LEV_CS(ED_NE,TOTAL)
     * value  = women studying (F_TOTAL − F_ED_NE)
     * value2 = men studying   (M_TOTAL − M_ED_NE).
     */
    private function parse451(array $data, string $geoKey): array
    {
        $geos = $data['dimension'][$geoKey]['category']['index'];
        $sexes = $data['dimension']['SEX']['category']['index'];
        $edus = $data['dimension']['EDU_LEV_CS']['category']['index'];
        $values = $data['value'];
        $result = [];
        $i = 0;

        foreach ($geos as $code) {
            $fTotal = null;
            $fNoEd = null;
            $mTotal = null;
            $mNoEd = null;

            foreach ($sexes as $sex) {
                foreach ($edus as $edu) {
                    $v = $values[$i++] ?? null;
                    if ('F' === $sex && 'TOTAL' === $edu) {
                        $fTotal = $v;
                    }
                    if ('F' === $sex && 'ED_NE' === $edu) {
                        $fNoEd = $v;
                    }
                    if ('M' === $sex && 'TOTAL' === $edu) {
                        $mTotal = $v;
                    }
                    if ('M' === $sex && 'ED_NE' === $edu) {
                        $mNoEd = $v;
                    }
                }
            }

            if (null === $fTotal || null === $fNoEd || null === $mTotal || null === $mNoEd) {
                continue;
            }

            $result[$code] = [(float) $fTotal - (float) $fNoEd, (float) $mTotal - (float) $mNoEd];
        }

        return $result;
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    private function fetch(string $url): array
    {
        return $this->http->request('GET', $url)->toArray();
    }

    private function fetchYear(string $urlTemplate, string $year): array
    {
        return $this->fetch(str_replace('[[[year]]]', $year, $urlTemplate));
    }

    private function stripLeadingZero(string $code): string
    {
        return ltrim($code, '0') ?: '0';
    }
}
