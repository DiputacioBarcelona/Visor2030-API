<?php

namespace App\Service\Etl\Importer;

use App\Entity\Indicator;
use App\Service\Etl\Dto\EtlContext;
use App\Service\Etl\Dto\IndicatorDefinition;
use App\Service\Etl\Util\NameGenderGuesser;

/**
 * Migrates indicators sourced from DIBA's open-data JSON API.
 *
 * Indicators:
 *   5.5.1 — % Càrrecs electes dones
 *            Counts elected officials per municipality from the live electes dataset,
 *            aggregating women (sexe = 'D') vs total. Falls back to first-name → gender
 *            inference (NameGenderGuesser) when the `sexe` field is blank in the source.
 *            Year is read from the dataset's `modificacio` timestamp (first 4 chars).
 *            Comarca and province values are derived from municipality counts.
 */
final class DibaImporter extends AbstractEtlImporter
{
    protected function getDefinitions(): array
    {
        return [
            '5.5.1' => new IndicatorDefinition(
                indicatorId: '5.5.1',
                targetId: '5.5',
                targetName: "Vetllar per la participació plena i efectiva de les dones, i per la igualtat d'oportunitats de lideratge en tots els àmbits de presa de decisions en la vida política, econòmica i pública.",
                sdg: 5,
                indicatorName: '% Càrrecs electes dones',
                indicatorDescription: 'Percentatge de càrrecs electes del consistori municipal ocupats per dones',
                sign: true,
                source: 'DIBA',
                unit: 'percent',
                scale: 1,
                url: 'https://do.diba.cat/api/dataset/electes/pag-fi/4000',
            ),
        ];
    }

    protected function import(IndicatorDefinition $def, Indicator $indicator, EtlContext $context): void
    {
        $data = $this->http->request('GET', $def->url)->toArray();

        $year = (int) substr($data['modificacio'] ?? '', 0, 4);
        if ($year <= 0) {
            throw new \RuntimeException('DIBA electes dataset missing valid `modificacio` year.');
        }

        $items = $data['elements'] ?? [];

        $dones = [];
        $total = [];

        foreach ($items as $item) {
            if (!isset($item['codi_ine_persona'][0]['ine'])) {
                continue;
            }
            $munCode = $item['codi_ine_persona'][0]['ine'];

            $sexe = $item['sexe'] ?? '';
            if ('' === $sexe) {
                $sexe = NameGenderGuesser::guess($item['nom'] ?? '');
            }

            $total[$munCode] = ($total[$munCode] ?? 0) + 1;
            if ('D' === $sexe) {
                $dones[$munCode] = ($dones[$munCode] ?? 0) + 1;
            }
        }

        foreach ($total as $munCode => $totalCount) {
            $municipality = $this->getMunicipalityByCode($munCode);
            if (!$municipality) {
                continue;
            }

            $this->setMunicipalityValue(
                $def,
                $indicator,
                $municipality,
                $year,
                (float) ($dones[$munCode] ?? 0),
                (float) $totalCount,
            );
        }

        $this->em->flush();
    }
}
