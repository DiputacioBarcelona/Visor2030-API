<?php

namespace App\Service\Etl\Importer;

use App\Entity\Indicator;
use App\Service\Etl\Dto\EtlContext;
use App\Service\Etl\Dto\IndicatorDefinition;
use App\Service\Etl\Enum\ImportScope;
use App\Service\Etl\Util\EtlUtils;

/**
 * Handles indicator 11.1.1 (rental economic effort), which requires two API
 * sources per year: Transparència Catalunya for rental prices and the IDESCAT
 * RFDB API for gross family income per capita.
 *
 * The IDESCAT income series currently ends at 2021; years beyond that are
 * skipped automatically because all municipality income lookups return null.
 */
final class DoHermesImporter extends AbstractEtlImporter
{
    protected function getDefinitions(): array
    {
        return [
            '11.1.1' => new IndicatorDefinition(
                indicatorId: '11.1.1',
                targetId: '11.1',
                targetName: 'Aconseguir progressivament un creixement dels ingressos del 40% més pobre de la població del territori a una taxa superior a la mitjana nacional.',
                sdg: 11,
                indicatorName: 'Esforç econòmic lloguer',
                indicatorDescription: 'Mitjana del preu anual del lloguer en relació amb la renda bruta familiar',
                sign: true,
                source: 'DO_HERMES',
                unit: 'abs',
                scale: 12,
                url: 'https://analisi.transparenciacatalunya.cat/resource/qww9-bvhh.json?periode=gener-desembre&$limit=10000&any=[[[year]]]',
                urlInfo: 'https://analisi.transparenciacatalunya.cat/resource/qww9-bvhh.json?$query=SELECT%20%60any%60%20GROUP%20BY%20%60any%60%20ORDER%20BY%20%60any%60%20DESC%20NULL%20FIRST',
                extra: [
                    'url2' => 'https://api.idescat.cat/taules/v2/rfdbc/13301/14148/mun/data?indicator=PER_CAPITA_EUR&concept=GROSS_INCOME&mun='.EtlUtils::BCN_MUNICIPALITY_FILTER.'&YEAR=[[[year]]]',
                ],
            ),
        ];
    }

    protected function import(IndicatorDefinition $def, Indicator $indicator, EtlContext $context): void
    {
        $years = $this->do->getYears($def->urlInfo);

        if ($this->shouldImport(ImportScope::Municipality)) {
            $this->fetchAndStoreMunicipalityData($def, $indicator, $years);
        }
    }

    private function fetchAndStoreMunicipalityData(IndicatorDefinition $def, Indicator $indicator, array $years): void
    {
        $url2Template = $def->extra['url2'];

        foreach ($years as $year) {
            $this->logger->debug(sprintf('Importing municipality data for year %s (%s)', $year, $def->indicatorId));

            $incomeByCode = $this->fetchIncomeByCode($url2Template, $year);

            if (empty($incomeByCode)) {
                $this->logger->debug(sprintf('No income data from IDESCAT for year %s — skipping.', $year));
                ++$this->skipped;
                continue;
            }

            $rentalData = $this->http->request('GET', str_replace('[[[year]]]', $year, $def->url))->toArray();

            $aggregated = [];
            foreach ($rentalData as $row) {
                $code = $row['codi_territorial'] ?? null;
                $renda = $row['renda'] ?? null;

                if (!$code || !$renda || !isset($incomeByCode[$code])) {
                    continue;
                }

                $aggregated[$code] = ($aggregated[$code] ?? 0.0) + (float) $renda;
            }

            foreach ($aggregated as $code => $value) {
                $mun = $this->getMunicipalityByCode($code);
                if (!$mun) {
                    continue;
                }

                $this->setMunicipalityValue($def, $indicator, $mun, (int) $year, $value, (float) $incomeByCode[$code]);
            }

            $this->em->flush();
            gc_collect_cycles();
        }
    }

    /**
     * Fetches IDESCAT gross income per capita for the given year.
     * Returns [5-digit-mun-code => income_value].
     * Returns empty array if IDESCAT has no data for that year.
     */
    private function fetchIncomeByCode(string $urlTemplate, string $year): array
    {
        $url = str_replace('[[[year]]]', $year, $urlTemplate);
        $data = $this->http->request('GET', $url)->toArray();

        $munis = $data['dimension']['MUN']['category']['index'] ?? [];
        $concepts = $data['dimension']['CONCEPT']['category']['index'] ?? [];
        $values = $data['value'] ?? [];

        if (empty($munis) || empty($concepts)) {
            return [];
        }

        $result = [];
        $i = 0;
        foreach ($munis as $mun) {
            $total = 0;
            foreach ($concepts as $ignored) {
                $total += $values[$i++] ?? 0;
            }
            // IDESCAT returns 6-digit codes; rental API uses 5-digit — align them.
            $result[substr($mun, 0, 5)] = $total;
        }

        return $result;
    }
}
