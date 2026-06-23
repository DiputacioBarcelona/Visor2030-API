<?php

namespace App\Service\Etl\Importer;

use App\Entity\Indicator;
use App\Service\Etl\Dto\EtlContext;
use App\Service\Etl\Dto\IndicatorDefinition;
use App\Service\Etl\Util\EtlUtils;

/**
 * Migrates indicators that combine Transparència Catalunya (Dades Obertes) data
 * as numerator with IDESCAT JSON API population data as denominator.
 *
 * Indicators:
 *   2.3.1 — Terres agrícoles per 100 hab.
 *            numerator : si4p-ygat (agricultural land, ha) — may return 403
 *            denominator: IDESCAT censph/538 (population, CONCEPT dim)
 *   4.1.1 — Taxa matriculació PFI
 *            numerator : xvme-26kg?codi_estudis=PFI (enrolments)
 *            denominator: IDESCAT censph/10 (pop ages 16–21, AGE dim)
 *   4.2.1 — Taxa educació infantil 1r cicle
 *            numerator : xvme-26kg?codi_estudis=EINF&nivell=1 (enrolments)
 *            denominator: IDESCAT censph/10 (pop ages 0–2, AGE dim)
 *
 * Comarca and province values are derived from municipality rows.
 *
 * Note: the legacy DOIDESCATService had $import_mun = false hardcoded — municipality
 * import was disabled. The new importer enables it correctly.
 */
final class DoIdescatImporter extends AbstractEtlImporter
{
    private const MUN_FILTER = 'mun='.EtlUtils::BCN_MUNICIPALITY_FILTER;

    protected function getDefinitions(): array
    {
        return [
            // ----------------------------------------------------------------
            // 2.3.1 — Terres agrícoles per 100 hab.
            // numerator  : DO si4p-ygat (sup_neta_h ha, lookup by municipality name)
            // denominator: IDESCAT censph/538 population (CONCEPT dim, 5-digit key)
            // WARNING: si4p-ygat returns HTTP 403 in current environment — same as 2.1.1.
            // ----------------------------------------------------------------
            '2.3.1' => new IndicatorDefinition(
                indicatorId: '2.3.1',
                targetId: '2.3',
                targetName: "Fomentar la productivitat agrícola i els ingressos de les persones que es dediquen a la producció d'aliments a petita escala.",
                sdg: 2,
                indicatorName: 'Terres agrícoles per 100 hab.',
                indicatorDescription: "Superfície total destinada a conreu (aliments i farratges) respecte del nombre d'habitants (hectàrees/100 habitants)",
                sign: true,
                source: 'DO_IDESCAT',
                unit: 'abs',
                scale: 100,
                url: 'https://analisi.transparenciacatalunya.cat/resource/si4p-ygat.json?$query=SELECT%0A%20%20%60id_mun%60%2C%0A%20%20%60campanya%60%2C%0A%20%20sum(%60sup_neta_h%60)%20AS%20%60sum_sup_neta_h%60%2C%0A%20%20%60nom_mun%60%0AWHERE%20caseless_eq(%60pro%60%2C%20%2208%22)%0AGROUP%20BY%20%60id_mun%60%2C%20%60campanya%60%2C%20%60nom_mun%60%0AHAVING%20caseless_one_of(%60campanya%60%2C%20%22[[[year]]]%22)',
                urlInfo: 'https://api.idescat.cat/taules/v2/censph/538/565/mun/?SEX=total&'.self::MUN_FILTER,
                extra: [
                    'url2' => 'https://api.idescat.cat/taules/v2/censph/538/565/mun/data?SEX=total&'.self::MUN_FILTER.'&YEAR=[[[year]]]',
                    'denomDim' => 'CONCEPT',
                    'denomCodeLen' => 5,
                    'numKey' => 'id_mun',
                    'numValue' => 'sum_sup_neta_h',
                    'lookupByName' => true,
                    'nameField' => 'nom_mun',
                ],
            ),

            // ----------------------------------------------------------------
            // 4.1.1 — Taxa matriculació PFI
            // numerator  : DO xvme-26kg (PFI enrolments by school → sum per muni)
            // denominator: IDESCAT censph/10 pop ages 16–21 (AGE dim, 6-digit key)
            // ----------------------------------------------------------------
            '4.1.1' => new IndicatorDefinition(
                indicatorId: '4.1.1',
                targetId: '4.1',
                targetName: "Reduir substancialment l'abandonament escolar prematur, assegurant l'accés a l'educació gratuïta, equitativa i de qualitat.",
                sdg: 4,
                indicatorName: 'Taxa matriculació PFI',
                indicatorDescription: "Alumnes matriculats a PFI sobre el total de població d'entre 16 i 21 anys al municipi.",
                sign: true,
                source: 'DO_IDESCAT',
                unit: 'abs',
                scale: 1,
                url: 'https://analisi.transparenciacatalunya.cat/resource/xvme-26kg.json?codi_estudis=PFI&$limit=10000&any=[[[year]]]',
                urlInfo: 'https://api.idescat.cat/taules/v2/censph/10/5975/mun/?AGE=Y016,Y017,Y018,Y019,Y020,Y021&SEX=total&'.self::MUN_FILTER,
                extra: [
                    'url2' => 'https://api.idescat.cat/taules/v2/censph/10/5975/mun/data?AGE=Y016,Y017,Y018,Y019,Y020,Y021&SEX=total&'.self::MUN_FILTER.'&YEAR=[[[year]]]',
                    'denomDim' => 'AGE',
                    'denomCodeLen' => 6,
                    'numKey' => 'codi_municip_6',
                    'numValue' => 'matr_cules_total',
                ],
            ),

            // ----------------------------------------------------------------
            // 4.2.1 — Taxa educació infantil 1r cicle
            // numerator  : DO xvme-26kg (EINF nivell 1 enrolments → sum per muni)
            // denominator: IDESCAT censph/10 pop ages 0–2 (AGE dim, 6-digit key)
            // ----------------------------------------------------------------
            '4.2.1' => new IndicatorDefinition(
                indicatorId: '4.2.1',
                targetId: '4.2',
                targetName: "Vetllar perquè tots els infants tinguin accés a serveis d'atenció i desenvolupament en la primera infantesa i a un ensenyament preescolar de qualitat.",
                sdg: 4,
                indicatorName: 'Taxa educació infantil 1r cicle',
                indicatorDescription: 'Percentatge infants EINF',
                sign: true,
                source: 'DO_IDESCAT',
                unit: 'abs',
                scale: 1,
                url: 'https://analisi.transparenciacatalunya.cat/resource/xvme-26kg.json?codi_estudis=EINF&nivell=1&$limit=10000&any=[[[year]]]',
                urlInfo: 'https://api.idescat.cat/taules/v2/censph/10/5975/mun/?AGE=Y000,Y001,Y002&SEX=total&'.self::MUN_FILTER,
                extra: [
                    'url2' => 'https://api.idescat.cat/taules/v2/censph/10/5975/mun/data?AGE=Y000,Y001,Y002&SEX=total&'.self::MUN_FILTER.'&YEAR=[[[year]]]',
                    'denomDim' => 'AGE',
                    'denomCodeLen' => 6,
                    'numKey' => 'codi_municip_6',
                    'numValue' => 'matr_cules_total',
                ],
            ),
        ];
    }

    protected function import(IndicatorDefinition $def, Indicator $indicator, EtlContext $context): void
    {
        $years = $this->idescatJson->getYears($def->urlInfo);

        foreach ($years as $year) {
            $doUrl = str_replace('[[[year]]]', $year, $def->url);
            $idescatUrl = str_replace('[[[year]]]', $year, $def->extra['url2']);

            $doData = $this->http->request('GET', $doUrl)->toArray();
            $idescatData = $this->http->request('GET', $idescatUrl)->toArray();

            $denominators = $this->parseDenominators(
                $idescatData,
                $def->extra['denomDim'],
                $def->extra['denomCodeLen']
            );

            $numerators = ($def->extra['lookupByName'] ?? false)
                ? $this->parseNumeratorsByName($doData, $def->extra['numKey'], $def->extra['numValue'], $def->extra['nameField'])
                : $this->parseNumeratorsByCode($doData, $def->extra['numKey'], $def->extra['numValue']);

            foreach ($numerators as $munCode => $value) {
                $population = $denominators[$munCode] ?? null;
                if (!$population) {
                    continue;
                }

                $municipality = $this->getMunicipalityByCode($munCode);
                if (!$municipality) {
                    continue;
                }

                $this->setMunicipalityValue($def, $indicator, $municipality, (int) $year, (float) $value, (float) $population);
            }

            $this->em->flush();
        }
    }

    /**
     * Sum MUN values across all category slots of the given IDESCAT dimension.
     * Returns [munCode (trimmed to $codeLen) => total].
     */
    private function parseDenominators(array $data, string $dimKey, int $codeLen): array
    {
        $munis = array_values($data['dimension']['MUN']['category']['index']);
        $cats = array_values($data['dimension'][$dimKey]['category']['index']);
        $vals = $data['value'];

        $result = [];
        $i = 0;
        foreach ($munis as $mun) {
            $total = 0;
            foreach ($cats as $_) {
                $total += (float) ($vals[$i] ?? 0);
                ++$i;
            }
            $result[substr((string) $mun, 0, $codeLen)] = $total;
        }

        return $result;
    }

    /**
     * Aggregate DO rows by municipality code field, summing the value field.
     * Returns [munCode => total].
     */
    private function parseNumeratorsByCode(array $data, string $codeField, string $valueField): array
    {
        $result = [];
        foreach ($data as $row) {
            $code = $row[$codeField] ?? null;
            $value = $row[$valueField] ?? null;
            if (null === $code || null === $value) {
                continue;
            }
            $result[$code] = ($result[$code] ?? 0) + (float) $value;
        }

        return $result;
    }

    /**
     * Aggregate DO rows by municipality code field, but only for rows whose
     * municipality name resolves in our DB. Returns [munCode => total].
     * Used for APIs where the code may be unreliable (e.g. 2.3.1 si4p-ygat).
     */
    private function parseNumeratorsByName(array $data, string $codeField, string $valueField, string $nameField): array
    {
        $result = [];
        foreach ($data as $row) {
            $name = $row[$nameField] ?? null;
            $code = $row[$codeField] ?? null;
            $value = $row[$valueField] ?? null;
            if (null === $name || null === $code || null === $value) {
                continue;
            }
            if (!$this->geo->getMunicipalityByName($name)) {
                continue;
            }
            $result[$code] = ($result[$code] ?? 0) + (float) $value;
        }

        return $result;
    }
}
