<?php

namespace App\Service\Etl\Importer;

use App\Entity\Indicator;
use App\Service\Etl\Dto\EtlContext;
use App\Service\Etl\Dto\IndicatorDefinition;
use App\Service\Etl\Enum\ImportScope;

final class PaesImporter extends AbstractEtlImporter
{
    protected function getDefinitions(): array
    {
        return [
            '7.2.1' => new IndicatorDefinition(
                indicatorId: '7.2.1',
                targetId: '7.2',
                targetName: "Augmentar substancialment la proporció d'energia renovable en el conjunt de les fonts energètiques.",
                sdg: 7,
                indicatorName: '% de llars amb energia renovable',
                indicatorDescription: "Percentatge d'energia renovable consumida (tèrmica i elèctrica) sobre l'energia total consumida (tèrmica, elèctrica i mobilitat) a les llars i el sector terciari.",
                sign: true,
                source: 'PAES',
                unit: 'percent',
                scale: 1,
                url: 'https://gissrv.diba.cat/arcgis/rest/services/SITAC/PAES/MapServer/4/query?f=json&where=(CE_ANY%20%3D%20[[[year]]])%20AND%20(1%3D1)&outFields=*&orderByFields=OBJECTID%20ASC',
                urlInfo: 'https://gissrv.diba.cat/arcgis/rest/services/SITAC/PAES/MapServer/4/query?f=json&where=(CE_CODI_INE%20%3D%20%2708019%27)%20AND%20(1%3D1)&outFields=*&orderByFields=CE_ANY%20DESC',
                extra: [
                    'url2' => 'https://gissrv.diba.cat/arcgis/rest/services/SITAC/PAES/MapServer/3/query?f=json&where=(CE_ANY%20%3D%20[[[year]]])%20AND%20(1%3D1)&outFields=*&orderByFields=OBJECTID%20ASC',
                ],
            ),
        ];
    }

    protected function import(IndicatorDefinition $def, Indicator $indicator, EtlContext $context): void
    {
        $years = $this->fetchYearsPaes($def->urlInfo);

        if ($this->shouldImport(ImportScope::Municipality)) {
            $this->fetchAndStoreMunicipalityData($def, $indicator, $years);
        }
    }

    /** Distinct CE_ANY values from the PAES ARCGIS MapServer/4 query. */
    private function fetchYearsPaes(string $url): array
    {
        $data = $this->http->request('GET', $url)->toArray();

        return array_map(fn ($row) => $row['attributes']['CE_ANY'] ?? null, $data['features'] ?? []);
    }

    private function fetchAndStoreMunicipalityData(IndicatorDefinition $def, Indicator $indicator, array $years): void
    {
        $url2Template = $def->extra['url2'];

        foreach ($years as $year) {
            $this->logger->debug(sprintf('Importing municipality data for year %s (%s)', $year, $def->indicatorId));

            $urlYear = str_replace('[[[year]]]', $year, $def->url);
            $url2Year = str_replace('[[[year]]]', $year, $url2Template);

            $features = $this->http->request('GET', $urlYear)->toArray()['features'];
            $features2 = $this->http->request('GET', $url2Year)->toArray()['features'];

            $denominators = [];
            foreach ($features2 as $row) {
                $attrs = $row['attributes'];
                $denominators[$attrs['CE_CODI_INE']] = $attrs;
            }

            foreach (array_chunk($features, 500) as $chunk) {
                foreach ($chunk as $row) {
                    $attrs = $row['attributes'];
                    $munCode = $attrs['CE_CODI_INE'];
                    $mun = $this->geo->getMunicipalityByCode($munCode);

                    if (!$mun) {
                        continue;
                    }

                    $denominator = $denominators[$munCode] ?? null;
                    if (null === $denominator) {
                        $this->logger->debug(sprintf(
                            'Skipping %s in %s for %s — no total energy denominator.',
                            $munCode,
                            $year,
                            $def->indicatorId
                        ));
                        continue;
                    }

                    $value = $attrs['EERR_TERMIC'] + $attrs['EERR_ELECTRIC'];
                    $value2 = $denominator['CE_TOTAL'];

                    $this->setMunicipalityValue($def, $indicator, $mun, (int) $year, $value, $value2);
                }
                gc_collect_cycles();
            }

            $this->em->flush();
            unset($features, $features2, $denominators);
        }
    }
}
