<?php

namespace App\Service\Etl\Importer;

use App\Entity\Indicator;
use App\Service\Etl\Dto\EtlContext;
use App\Service\Etl\Dto\IndicatorDefinition;
use App\Service\Etl\Enum\ImportScope;

final class IneImporter extends AbstractEtlImporter
{
    protected function getDefinitions(): array
    {
        return [
            '1.1.1' => new IndicatorDefinition(
                indicatorId: '1.1.1',
                targetId: '1.1',
                targetName: 'Erradicar la pobresa extrema',
                sdg: 1,
                indicatorName: '% Població amb ingressos < 40%',
                indicatorDescription: 'Percentatge de població que viu en unitats de consum amb una renda disponible inferior al 40% de la mitjana',
                sign: true,
                source: 'INE',
                unit: 'percent',
                scale: 1,
                url: 'https://servicios.ine.es/wstempus/js/ES/datos_tabla/30901?tv=19:&tv=848:322970&tv=18:451',
            ),
            '1.2.1' => new IndicatorDefinition(
                indicatorId: '1.2.1',
                targetId: '1.2',
                targetName: "Reduir la proporció de població que viu en la pobresa, augmentant els programes integrals que l'abordin en totes les seves dimensions.",
                sdg: 1,
                indicatorName: '% Població amb ingressos < 60%',
                indicatorDescription: 'Percentatge de població que viu en unitats de consum amb una renda disponible inferior al 60% de la mitjana',
                sign: true,
                source: 'INE',
                unit: 'percent',
                scale: 1,
                url: 'https://servicios.ine.es/wstempus/js/ES/datos_tabla/30901?tv=19:&tv=848:322972&tv=18:451',
            ),
            '1.2.2' => new IndicatorDefinition(
                indicatorId: '1.2.2',
                targetId: '1.2',
                targetName: "Reduir la proporció de població que viu en la pobresa, augmentant els programes integrals que l'abordin en totes les seves dimensions.",
                sdg: 1,
                indicatorName: 'Renda mediana',
                indicatorDescription: "Valor de renda que, ordenant a tots els individus de menor a major ingrés, deixa una meitat dels mateixos per sota d'aquest valor i l'altra meitat per sobre",
                sign: true,
                source: 'INE',
                unit: 'renda',
                scale: 1,
                url: 'https://servicios.ine.es/wstempus/js/ES/datos_tabla/30896?tv=19:&tv=482:382441',
                urlProv: 'https://servicios.ine.es/wstempus/js/ES/datos_tabla/53689?tv=482:382441&tv=115:9',
            ),
            '10.1.1' => new IndicatorDefinition(
                indicatorId: '10.1.1',
                targetId: '10.1',
                targetName: 'Aconseguir progressivament un creixement dels ingressos del 40% més pobre de la població del territori a una taxa superior a la mitjana nacional.',
                sdg: 10,
                indicatorName: '% Població amb ingressos < 40%',
                indicatorDescription: 'Percentatge de població que viu en unitats de consum amb una renda disponible inferior al 40% de la mitjana',
                sign: true,
                source: 'INE',
                unit: 'percent',
                scale: 1,
                url: 'https://servicios.ine.es/wstempus/js/ES/datos_tabla/30901?tv=19:&tv=848:322970&tv=18:451',
            ),
            '10.1.2' => new IndicatorDefinition(
                indicatorId: '10.1.2',
                targetId: '10.1',
                targetName: 'Aconseguir progressivament un creixement dels ingressos del 40% més pobre de la població del territori a una taxa superior a la mitjana nacional.',
                sdg: 10,
                indicatorName: 'Índex Gini',
                indicatorDescription: "Grau de desigualtat en una distribució d'una variable contínua. S'obté a partir de la suma de les diferències absolutes entre cada parell de rendes de la distribució.",
                sign: true,
                source: 'INE',
                unit: 'index',
                scale: 1,
                url: 'https://servicios.ine.es/wstempus/js/ES/datos_tabla/37686?tv=19:&tv=482:382445',
            ),
            '10.1.3' => new IndicatorDefinition(
                indicatorId: '10.1.3',
                targetId: '10.1',
                targetName: 'Aconseguir progressivament un creixement dels ingressos del 40% més pobre de la població del territori a una taxa superior a la mitjana nacional.',
                sdg: 10,
                indicatorName: 'Desigualtat per Renda',
                indicatorDescription: "Desigualtat en la distribució a través de ràtios entre centils, que s'interpreta com la renda que s'obté per al quintil superior (és a dir, el 20% de la població amb un nivell econòmic més alt) en relació amb la del quintil inferior.",
                sign: true,
                source: 'INE',
                unit: 'ratio',
                scale: 1,
                url: 'https://servicios.ine.es/wstempus/js/ES/datos_tabla/37686?tv=19:&tv=482:382446',
            ),
            '10.4.1' => new IndicatorDefinition(
                indicatorId: '10.4.1',
                targetId: '10.4',
                targetName: 'Impulsar polítiques, en especial fiscals, salarials i de protecció social per aconseguir més igualtat al territori.',
                sdg: 10,
                indicatorName: '% població amb ingressos < 50% (homes)',
                indicatorDescription: '% població amb ingressos < 50% (homes)',
                sign: true,
                source: 'INE',
                unit: 'ratio',
                scale: 1,
                url: 'https://servicios.ine.es/wstempus/js/ES/datos_tabla/30901?tv=19:&tv=848:322971&tv=18:452&tv=18:453',
            ),
            '10.4.2' => new IndicatorDefinition(
                indicatorId: '10.4.2',
                targetId: '10.4',
                targetName: 'Impulsar polítiques, en especial fiscals, salarials i de protecció social per aconseguir més igualtat al territori.',
                sdg: 10,
                indicatorName: '% població amb ingressos < 50% (dones)',
                indicatorDescription: '% població amb ingressos < 50% (dones)',
                sign: true,
                source: 'INE',
                unit: 'ratio',
                scale: 1,
                url: 'https://servicios.ine.es/wstempus/js/ES/datos_tabla/30901?tv=19:&tv=848:322971&tv=18:452&tv=18:453',
            ),
        ];
    }

    protected function import(IndicatorDefinition $def, Indicator $indicator, EtlContext $context): void
    {
        // For 1.2.2, comarca values are derived from municipality data, so we
        // must fetch mun even when only --scope=comarca was requested.
        // Mun writes are still skipped by the base; only the fetch runs.
        $munNeededForComarca = '1.2.2' === $def->indicatorId
            && $this->shouldImport(ImportScope::Comarca);

        $munData = [];
        if ($this->shouldImport(ImportScope::Municipality) || $munNeededForComarca) {
            $munData = $this->fetchAndStoreMunicipalityData($def, $indicator);
        }

        if ($this->shouldImport(ImportScope::Comarca) && '1.2.2' === $def->indicatorId) {
            $this->fetchAndStoreComarcaData($def, $indicator, $munData);
        }

        if ($this->shouldImport(ImportScope::Province) && $def->urlProv) {
            $this->fetchAndStoreProvinceData($def, $indicator);
        }
    }

    /**
     * Fetches municipality data from INE API and stores it.
     * Returns [year => [municipalityCode => value]] needed for 1.2.2 comarca derivation.
     */
    private function fetchAndStoreMunicipalityData(IndicatorDefinition $def, Indicator $indicator): array
    {
        $indicatorId = $def->indicatorId;

        $response = $this->http->request('GET', $def->url);
        $data = $response->toArray();

        $munData = [];

        foreach ($data as $item) {
            $municipiName = explode('.', $item['Nombre'])[0];
            $municipality = $this->geo->getMunicipalityByName($municipiName);

            if (!$municipality) {
                continue;
            }

            $subindicator = null;
            if ('10.4.1' === $indicatorId || '10.4.2' === $indicatorId) {
                $segmentName = trim(explode('.', $item['Nombre'])[1]);
                $subindicator = 'Hombres' === $segmentName ? null : 2;
            }

            foreach ($item['Data'] as $yearData) {
                $year = $yearData['Anyo'];
                $value = $yearData['Valor'];

                if ('1.2.2' === $indicatorId) {
                    $munData[$year] ??= [];
                    $munData[$year][$municipality->getMunicipalityCode()] = $value;
                }

                if (null === $value) {
                    $this->logger->debug(sprintf(
                        'Skipping %s in %d for %s — value is null.',
                        $municipiName,
                        $year,
                        $indicatorId
                    ));
                    continue;
                }

                // 10.4.1 keeps only male rows; 10.4.2 keeps only female rows.
                // Both are stored with subindicator=null — the gender is encoded in
                // the indicator_id itself, not the subindicator column. This matches
                // the legacy INEService behaviour; changing it would break existing data.
                if ('10.4.1' === $indicatorId && null !== $subindicator) {
                    continue;
                }
                if ('10.4.2' === $indicatorId && 2 !== $subindicator) {
                    continue;
                }

                $this->setMunicipalityValue($def, $indicator, $municipality, $year, (float) $value, null, null);
            }
        }

        return $munData;
    }

    /**
     * Derives comarca values for indicator 1.2.2 via population-weighted averaging.
     * Requires the $munData map produced by fetchAndStoreMunicipalityData().
     */
    private function fetchAndStoreComarcaData(IndicatorDefinition $def, Indicator $indicator, array $munData): void
    {
        $comarquesMap = $this->geo->getAllComarques();
        $municipalitiesByComarca = [];

        foreach ($munData as $year => $yearData) {
            $munPopulations = $this->do->getMunicipalityPopulationByYear($year, true);
            $comPopulations = $this->do->getComarcaPopulationByYear($year);

            foreach ($comPopulations as $comCode => $population) {
                if (0 === $population) {
                    continue;
                }

                $comarca = $comarquesMap[$comCode];

                $municipalitiesByComarca[$comCode] ??= $this->geo->getMunicipalityCodesByComarcaCode($comCode);
                $munCodes = $municipalitiesByComarca[$comCode];

                $weightedSum = 0;
                foreach ($munCodes as $munCode) {
                    $munValue = $yearData[$munCode] ?? null;
                    $munPop = $munPopulations[$munCode] ?? 0;
                    if (null === $munValue) {
                        continue;
                    }
                    $weightedSum += $munValue * $munPop;
                }

                $value = $weightedSum / $population;

                $this->setComarcaValue($def, $indicator, $comarca, $year, $value);
            }
        }
    }

    /**
     * Fetches and stores province-level data (only 1.2.2 has a urlProv).
     */
    private function fetchAndStoreProvinceData(IndicatorDefinition $def, Indicator $indicator): void
    {
        $response = $this->http->request('GET', $def->urlProv);
        $data = $response->toArray()[0];
        $province = $this->geo->getProvince();

        foreach ($data['Data'] as $yearData) {
            $year = $yearData['Anyo'];
            $value = $yearData['Valor'];

            if (null === $value) {
                $this->logger->debug(sprintf(
                    'Skipping Barcelona province in %d for %s — value is null.',
                    $year,
                    $def->indicatorId
                ));
                continue;
            }

            $this->setProvinceValue($def, $indicator, $province, $year, (float) $value);
        }
    }
}
