<?php

namespace App\Service\Etl\Importer;

use App\Entity\Indicator;
use App\Service\Etl\Dto\EtlContext;
use App\Service\Etl\Dto\IndicatorDefinition;

/**
 * Migrates indicators that combine DIBA JSON API data (numerator) with
 * IDESCAT SSV table data (denominator).
 *
 * Indicators:
 *   1.4.1 — Taxa de prestacions desocupació
 *            numerator : DIBA (beneficiaris de prestacions per atur)
 *            denominator: IDESCAT atureg (aturats registrats amb ocupació anterior)
 */
final class DibaIdescatImporter extends AbstractEtlImporter
{
    protected function getDefinitions(): array
    {
        return [
            '1.4.1' => new IndicatorDefinition(
                indicatorId: '1.4.1',
                targetId: '1.4',
                targetName: 'Garantir que les persones, especialment les pobres i vulnerables, tinguin els mateixos drets, així com accés als serveis bàsics, recursos naturals, econòmics i financers en igualtat de condicions.',
                sdg: 1,
                indicatorName: 'Taxa de prestacions desocupació',
                indicatorDescription: "Nombre de persones beneficiàries de prestacions respecte el total d'aturats registrats que han tingut ocupació anterior",
                sign: true,
                source: 'DIBA',
                unit: 'percent',
                scale: 1,
                // DIBA prestacions per atur (numerator); [[[year]]] replaced per iteration
                url: 'https://www.diba.cat/hg2/presentacioMun.asp?prId=1327&idioma=cat&codi_any=[[[year]]]&codi_mes=6&format=json',
                // IDESCAT atureg – used to discover available years
                urlInfo: 'https://www.idescat.cat/pub/?id=atureg&n=4299&geo=cat&f=ssv',
                extra: [
                    // IDESCAT atureg per municipality (denominator); [[[year]]] replaced per iteration
                    'urlAtur' => 'https://www.idescat.cat/pub/?id=atureg&n=4299&geo=mun&t=[[[year]]]00&f=ssv',
                ],
            ),
        ];
    }

    protected function import(IndicatorDefinition $def, Indicator $indicator, EtlContext $context): void
    {
        $years = $this->idescatTable->getYears($def->urlInfo);

        foreach ($years as $year) {
            $dibaUrl = str_replace('[[[year]]]', $year, $def->url);
            $aturUrl = str_replace('[[[year]]]', $year, $def->extra['urlAtur']);

            $dibaData = $this->http->request('GET', $dibaUrl)->toArray()['data'] ?? [];
            $denominators = $this->idescatTable->getValues($aturUrl, true);

            foreach ($dibaData as $muni) {
                $value = $muni['value'];
                $munCode = $muni['id'];

                if (!is_numeric($value)) {
                    continue;
                }

                $municipality = $this->getMunicipalityByCode($munCode);
                if (!$municipality) {
                    continue;
                }

                $raw = $denominators[$munCode]['Sexe. Total'] ?? null;
                if (!$raw) {
                    continue;
                }
                $value2 = (float) str_replace(',', '.', $raw);

                $this->setMunicipalityValue($def, $indicator, $municipality, (int) $year, (float) $value, $value2);
            }

            $this->em->flush();
        }
    }
}
