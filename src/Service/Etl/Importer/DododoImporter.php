<?php

namespace App\Service\Etl\Importer;

use App\Entity\Indicator;
use App\Service\Etl\Dto\EtlContext;
use App\Service\Etl\Dto\IndicatorDefinition;
use App\Service\Etl\Enum\ImportScope;

final class DododoImporter extends AbstractEtlImporter
{
    protected function getDefinitions(): array
    {
        return [
            '7.3.1' => new IndicatorDefinition(
                indicatorId: '7.3.1',
                targetId: '7.3',
                targetName: "Millorar substancialment l'eficiència energètica.",
                sdg: 7,
                indicatorName: "Consum d'energia a les llars",
                indicatorDescription: "Consum final a les llars (en kWh/habitant) incloent tots els tipus d'energia. Els consums de gasoil de calefacció, biomassa, butà i propà són estimacions fetes a partir dels consums provincials o catalans.",
                sign: true,
                source: 'PAES',
                unit: 'percent',
                scale: 1,
                // NOTE: the electricity URL below has "2023" hardcoded instead of a [[[year]]]
                // placeholder — this matches legacy DODODOService behaviour. For all years the
                // electricity component comes from 2023 data; only the gas component (url2) uses
                // the correct year. This is a known issue carried over from the legacy service.
                url: 'https://analisi.transparenciacatalunya.cat/resource/8idm-becu.json?$query=SELECT%0A%20%20%60any%60%2C%0A%20%20%60provincia%60%2C%0A%20%20%60comarca%60%2C%0A%20%20%60cdmun%60%2C%0A%20%20%60municipi%60%2C%0A%20%20%60codi_sector%60%2C%0A%20%20%60descripcio_sector%60%2C%0A%20%20%60consum_kwh%60%2C%0A%20%20%60observacions%60%0AWHERE%20%60any%60%20IN%20(%222023%22)%20AND%20caseless_one_of(%60codi_sector%60%2C%20%227%22)%20AND%20caseless_one_of(%60provincia%60%2C%20%22BARCELONA%22)',
                urlInfo: 'https://analisi.transparenciacatalunya.cat/resource/8idm-becu.json?$query=SELECT%20%60any%60%20GROUP%20BY%20%60any%60%20ORDER%20BY%20%60any%60%20DESC%20NULL%20LAST',
                extra: [
                    'url2' => 'https://analisi.transparenciacatalunya.cat/resource/qvqg-zag8.json?$query=SELECT%0A%20%20%60any%60%2C%0A%20%20%60provincia%60%2C%0A%20%20%60comarca%60%2C%0A%20%20%60cdmun%60%2C%0A%20%20%60municipi%60%2C%0A%20%20%60sector%60%2C%0A%20%20%60consum_kwh_pcs%60%2C%0A%20%20%60observacions%60%0AWHERE%20caseless_one_of(%60sector%60%2C%20%22DOM%C3%88STIC%22)%20AND%20%60any%60%20IN%20(%22[[[year]]]%22)%20AND%20caseless_one_of(%60provincia%60%2C%20%22BARCELONA%22)',
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

            // url has year hardcoded (see comment in getDefinitions); str_replace is a no-op here.
            $urlYear = str_replace('[[[year]]]', $year, $def->url);
            $url2Year = str_replace('[[[year]]]', $year, $url2Template);

            $dataElec = $this->http->request('GET', $urlYear)->toArray();
            $dataGas = $this->http->request('GET', $url2Year)->toArray();

            $gasIndexed = [];
            foreach ($dataGas as $row) {
                $gasIndexed[$row['cdmun']] = $row['consum_kwh_pcs'] ?? 0;
            }

            $populations = $this->do->getMunicipalityPopulationByYear($year);

            foreach ($dataElec as $row) {
                $munCode = $row['cdmun'];
                $mun = $this->geo->getMunicipalityByCode($munCode);

                if (!$mun) {
                    continue;
                }

                $munCode6 = $mun->getMunicipalityCode6();
                $population = $populations[$munCode6] ?? null;

                if (!$population) {
                    $this->logger->error(sprintf(
                        'Population not found for %s in year %s — skipping.',
                        $munCode6,
                        $year
                    ));
                    continue;
                }

                $value = ($gasIndexed[$munCode] ?? 0) + $row['consum_kwh'];
                $value2 = $population;

                $this->setMunicipalityValue($def, $indicator, $mun, (int) $year, $value, $value2);
            }

            $this->em->flush();
            gc_collect_cycles();
        }
    }
}
