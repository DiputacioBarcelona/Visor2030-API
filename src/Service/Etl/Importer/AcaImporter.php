<?php

namespace App\Service\Etl\Importer;

use App\Entity\Indicator;
use App\Service\Etl\Dto\EtlContext;
use App\Service\Etl\Dto\IndicatorDefinition;
use App\Service\Etl\Enum\ImportScope;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Handles ACA (Agència Catalana de l'Aigua) Excel-based indicators.
 *
 * 6.1.1 — Water price (€/m³): one year tab per sheet; col 0 = INE code, col 6 = total price.
 *          No comarca/province derivation.
 *
 * 6.4.1 — Water consumption per capita: single sheet; years in header row (row 6, col 3+);
 *          every year occupies 3 columns (Domèstic, Activitats, Total); we import Total.
 *          Comarca and province values are derived from municipalities.
 *
 * NOTE: 6.4.1 URL was broken in the legacy service (wrong path "20_Agua" → "20_Aigua").
 *       Fixed here. If the file structure changes again this importer may need updating.
 */
final class AcaImporter extends AbstractEtlImporter
{
    protected function getDefinitions(): array
    {
        return [
            '6.1.1' => new IndicatorDefinition(
                indicatorId: '6.1.1',
                targetId: '6.1',
                targetName: "Assegurar l'accés universal i equitatiu a l'aigua potable, a un preu assequible amb especial atenció als col·lectius més vulnerables.",
                sdg: 6,
                indicatorName: "Preu de l'aigua €/m3",
                indicatorDescription: "Preu de l'aigua (en € per m3) inclouent el preu del subminitrament, el cànon de l'aigua i el clavegueram",
                sign: true,
                source: 'ACA',
                unit: 'abs',
                scale: 1,
                url: 'https://aca.gencat.cat/web/.content/10_ACA/L_Observatori_preu_aigua/03-Preu-per-municipis-i-evolucio/Preus_per_municipi_ca.xlsx',
            ),
            '6.4.1' => new IndicatorDefinition(
                indicatorId: '6.4.1',
                targetId: '6.4',
                targetName: "Augmentar significativament l'ús eficient dels recursos hídrics en tots els sectors i assegurar la sostenibilitat de l'extracció i del proveïment d'aigua dolça.",
                sdg: 6,
                indicatorName: "Consum d'aigua per habitant",
                indicatorDescription: "Volum d'aigua domèstica consumida (en m3 per habitant i any).",
                sign: true,
                source: 'ACA',
                unit: 'abs',
                scale: 1,
                url: 'https://aca.gencat.cat/web/.content/20_Aigua/08_consulta_de_dades/01_dades_obertes/01_visualitzacio_interactiva_dades/01_consum-aigua_comarques_catalunya/volum-aigua-consum-municipi.xlsx',
            ),
        ];
    }

    protected function import(IndicatorDefinition $def, Indicator $indicator, EtlContext $context): void
    {
        if ('6.1.1' === $def->indicatorId) {
            $this->import611($def, $indicator);
        } else {
            $this->import641($def, $indicator);
        }
    }

    // -------------------------------------------------------------------------
    // 6.1.1 — Water price
    // -------------------------------------------------------------------------

    private function import611(IndicatorDefinition $def, Indicator $indicator): void
    {
        if (!$this->shouldImport(ImportScope::Municipality)) {
            return;
        }

        $spreadsheet = $this->downloadSpreadsheet($def->url);
        $sheetNames = array_filter(
            $spreadsheet->getSheetNames(),
            static fn (string $name) => (bool) preg_match('/^\d{4}$/', $name)
        );

        foreach ($sheetNames as $year) {
            $this->logger->debug(sprintf('Importing 6.1.1 year %s', $year));
            $sheet = $spreadsheet->getSheetByName($year);

            foreach ($sheet->getRowIterator() as $row) {
                $cells = [];
                $col = 0;
                foreach ($row->getCellIterator() as $cell) {
                    $cells[$col++] = $cell->getValue();
                }

                $code = (string) ($cells[0] ?? '');
                $value = $cells[6] ?? null;

                if (!preg_match('/^\d/', $code) || null === $value || '' === $value) {
                    continue;
                }

                $mun = $this->getMunicipalityByCode($code);
                if (!$mun) {
                    continue;
                }

                $this->setMunicipalityValue($def, $indicator, $mun, (int) $year, (float) $value);
            }

            $this->em->flush();
            gc_collect_cycles();
        }
    }

    // -------------------------------------------------------------------------
    // 6.4.1 — Water consumption per capita
    // -------------------------------------------------------------------------

    private function import641(IndicatorDefinition $def, Indicator $indicator): void
    {
        if ($this->shouldImport(ImportScope::Municipality)) {
            $spreadsheet = $this->downloadSpreadsheet($def->url);
            $sheet = $spreadsheet->getActiveSheet();

            // Row 6 (0-indexed 5) contains year values at columns 3, 6, 9, …
            // Cols per year: Domèstic | Activitats | Total — we want Total (offset +2).
            $yearColumns = $this->readYearColumns($sheet);

            foreach ($yearColumns as $year => $totalCol) {
                $this->logger->debug(sprintf('Importing 6.4.1 year %s (col %d)', $year, $totalCol));

                $populations = $this->do->getMunicipalityPopulationByYear((string) $year);
                if (empty($populations)) {
                    $populations = $this->do->getMunicipalityPopulationLastYear();
                }

                foreach ($sheet->getRowIterator() as $rowIndex => $row) {
                    if ($rowIndex < 8) { // skip header rows (1-indexed)
                        continue;
                    }

                    $cells = [];
                    $col = 0;
                    foreach ($row->getCellIterator() as $cell) {
                        $cells[$col++] = $cell->getValue();
                    }

                    $code = (string) ($cells[0] ?? '');
                    $value = $cells[$totalCol] ?? null;

                    if (!preg_match('/^\d/', $code) || null === $value || '' === $value) {
                        continue;
                    }

                    $mun = $this->getMunicipalityByCode($code);
                    if (!$mun) {
                        continue;
                    }

                    $munCode6 = $mun->getMunicipalityCode6();
                    $population = $populations[$munCode6] ?? null;
                    if (!$population) {
                        continue;
                    }

                    $this->setMunicipalityValue($def, $indicator, $mun, $year, (float) $value, (float) $population);
                }

                $this->em->flush();
                gc_collect_cycles();
            }
        }
    }

    /**
     * Reads the year header row (row 6, 1-indexed) and returns [year => totalColumnIndex].
     * Each year occupies 3 columns starting at column 3; Total is the 3rd column (offset +2).
     */
    private function readYearColumns(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet): array
    {
        $result = [];
        $headerRow = $sheet->getRowIterator(6, 6)->current();
        $col = 0;

        foreach ($headerRow->getCellIterator() as $cell) {
            $v = $cell->getValue();
            if ($col >= 3 && is_numeric($v) && (int) $v > 2000) {
                $result[(int) $v] = $col + 2; // +2 = Total column offset within the year block
            }
            ++$col;
        }

        return $result;
    }

    // -------------------------------------------------------------------------
    // Shared helper
    // -------------------------------------------------------------------------

    private function downloadSpreadsheet(string $url): \PhpOffice\PhpSpreadsheet\Spreadsheet
    {
        $response = $this->http->request('GET', $url);
        $tmpFile = tempnam(sys_get_temp_dir(), 'aca_').'.xlsx';
        file_put_contents($tmpFile, $response->getContent());

        try {
            return IOFactory::load($tmpFile);
        } finally {
            @unlink($tmpFile);
        }
    }
}
