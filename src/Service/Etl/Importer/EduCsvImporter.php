<?php

namespace App\Service\Etl\Importer;

use App\Entity\Indicator;
use App\Service\Etl\Dto\EtlContext;
use App\Service\Etl\Dto\IndicatorDefinition;

/**
 * Migrates indicators derived from Generalitat de Catalunya ESO CSV files.
 *
 * Indicators:
 *   4.1.2 — Taxa graduació ESO
 *            Graduats d'educació secundària obligatòria (4t ESO) respecte el total de matriculats.
 *
 * Data source: educacio.gencat.cat semicolon-separated CSVs.
 * Column layouts differ between the "current" year CSV and historical CSVs.
 */
final class EduCsvImporter extends AbstractEtlImporter
{
    // Column indices for the current-year CSV (newer format, 17 columns per row)
    private const COL_CURRENT = [
        'curs' => 0,
        'mun' => 6,
        'nivell' => 12,
        'resultat' => 15,
        'alumnes' => 16,
    ];

    // Column indices for historical CSVs (older format, 16 columns per row)
    private const COL_HISTORIC = [
        'curs' => 0,
        'mun' => 6,
        'nivell' => 11,
        'resultat' => 14,
        'alumnes' => 15,
    ];

    protected function getDefinitions(): array
    {
        return [
            '4.1.2' => new IndicatorDefinition(
                indicatorId: '4.1.2',
                targetId: '4.1',
                targetName: "Reduir substancialment l'abandonament escolar prematur, assegurant l'accés a l'educació gratuïta, equitativa i de qualitat.",
                sdg: 4,
                indicatorName: 'Taxa graduació ESO',
                indicatorDescription: "Graduats d'educació secundària obligatòria del municipi respecte el total de matriculats a 4t d'ESO",
                sign: true,
                source: 'EDU_CSV',
                unit: 'percent',
                scale: 1,
                // Current-year CSV (no year placeholder)
                url: 'https://educacio.gencat.cat/web/.content/home/departament/estadistiques/estadistiques-ensenyament/curs-actual/eso/eso-a-csv-02.csv',
                extra: [
                    // Historical CSV URL — [[[year]]] = academic year string e.g. '2022-2023'
                    'urlHistoric' => 'https://educacio.gencat.cat/web/.content/home/departament/estadistiques/estadistiques-ensenyament/cursos-anteriors/curs-[[[year]]]/eso/eso-a-csv-02.csv',
                    'historicYears' => ['2022-2023'],
                ],
            ),
        ];
    }

    protected function import(IndicatorDefinition $def, Indicator $indicator, EtlContext $context): void
    {
        // Current year CSV
        $this->importFromUrl($def, $indicator, $def->url, self::COL_CURRENT);

        // Historical year CSVs
        foreach ($def->extra['historicYears'] as $academicYear) {
            $url = str_replace('[[[year]]]', $academicYear, $def->extra['urlHistoric']);
            $this->importFromUrl($def, $indicator, $url, self::COL_HISTORIC);
        }
    }

    private function importFromUrl(IndicatorDefinition $def, Indicator $indicator, string $url, array $cols): void
    {
        $csv = $this->http->request('GET', $url)->getContent();
        $grouped = $this->parseCsv($csv, $cols);

        foreach ($grouped as $munCode => ['year' => $year, 'passed' => $passed, 'total' => $total]) {
            $municipality = $this->getMunicipalityByCode($munCode);
            if (!$municipality) {
                continue;
            }
            $this->setMunicipalityValue($def, $indicator, $municipality, (int) $year, (float) $passed, (float) $total);
        }

        $this->em->flush();
    }

    /**
     * Parse the Generalitat ESO CSV, returning aggregated rows keyed by municipality code.
     * Only rows with Nivell=4 and municipality codes starting with '08' are included.
     *
     * @param array $cols Column index map: curs, mun, nivell, resultat, alumnes
     *
     * @return array<string, array{year: string, passed: int, total: int}>
     */
    private function parseCsv(string $csv, array $cols): array
    {
        $lines = explode("\n", $csv);
        $grouped = [];
        $year = null;

        $isFirst = true;
        foreach ($lines as $line) {
            $line = trim($line);
            if ('' === $line) {
                continue;
            }

            // Skip the header line
            if ($isFirst) {
                $isFirst = false;
                continue;
            }

            $info = str_getcsv($line, ';');
            if (count($info) <= max($cols)) {
                continue;
            }

            $nivell = $info[$cols['nivell']] ?? '';
            $munCode = $info[$cols['mun']] ?? '';

            // Only 4th year of ESO, Barcelona province municipalities
            if ('4' !== $nivell || '08' !== substr($munCode, 0, 2)) {
                continue;
            }

            $curs = $info[$cols['curs']] ?? '';
            $resultat = $info[$cols['resultat']] ?? '';
            $alumnes = (int) ($info[$cols['alumnes']] ?? 0);

            // Extract year from academic year string (e.g. "2022/2023" → "2023")
            if (null === $year) {
                $year = substr($curs, -4);
            }

            if (!isset($grouped[$munCode])) {
                $grouped[$munCode] = ['year' => $year, 'passed' => 0, 'total' => 0];
            }

            $grouped[$munCode]['total'] += $alumnes;

            if ('PROMOCIONEN SENSE PENDENTS' === $resultat || 'PROMOCIONEN AMB PENDENTS' === $resultat) {
                $grouped[$munCode]['passed'] += $alumnes;
            }
        }

        return $grouped;
    }
}
