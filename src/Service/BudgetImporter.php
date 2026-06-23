<?php

namespace App\Service;

use App\Entity\Budget;
use App\Service\Etl\Geo\GeoRegistry;
use App\Service\Etl\Util\EtlUtils;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Imports municipal budget data from the Populate Tools gobierto-budgets-data
 * repository (gzipped SQL dumps of `tb_inventario` and `tb_funcional`).
 *
 * Indexed by year. Writes Budget entities, one per (year, program, municipality).
 * Triggered by app:import-budgets.
 */
class BudgetImporter
{
    /** @var array<string, array{codbdgel: string}> */
    private array $inventarioData = [];

    private ?int $year = null;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly HttpClientInterface $httpClient,
        private readonly GeoRegistry $geo,
    ) {
    }

    public function import(int $year): void
    {
        $this->year = $year;

        $inventarioData = $this->importInventario($year);
        $this->importFuncional($year, $inventarioData);
    }

    /**
     * Downloads, gunzips, and returns the SQL dump from gobierto-budgets-data.
     */
    private function fetchAndExtractSql(string $url): string
    {
        $response = $this->httpClient->request('GET', $url);

        if (200 !== $response->getStatusCode()) {
            throw new \RuntimeException("Failed to download SQL file: $url");
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'sql_');
        file_put_contents($tempFile, $response->getContent());

        $gz = gzopen($tempFile, 'r');
        $sql = '';
        while (!gzeof($gz)) {
            $sql .= gzread($gz, 4096);
        }
        gzclose($gz);
        unlink($tempFile);

        return $sql;
    }

    /**
     * Parses tb_inventario.sql.gz, keeps rows where codbdgel starts with "08"
     * (Barcelona province) and the area code (chars 5-7) is "AA" (the ajuntament
     * entity itself, not its dependent organisations). Returns inventario rows
     * keyed by id.
     */
    private function importInventario(int $year): array
    {
        $url = "https://raw.githubusercontent.com/PopulateTools/gobierto-budgets-data/master/data/presupuestos_municipales/{$year}/planned/tb_inventario.sql.gz";
        $sql = $this->fetchAndExtractSql($url);
        $sql = str_replace('"', '`', $sql);

        foreach (explode(';', $sql) as $statement) {
            $statement = trim($statement);
            if ('' === $statement) {
                continue;
            }

            if (!preg_match('/INSERT INTO `tb_inventario` \((.*?)\) VALUES \((.*?)\)/i', $statement, $matches)) {
                continue;
            }

            $columns = array_map('trim', explode(',', str_replace('`', '', $matches[1])));
            $values = array_map(fn ($v) => trim($v, " '"), explode(',', $matches[2]));

            $idIndex = array_search('id', $columns);
            $codbdgelIndex = array_search('codbdgel', $columns);

            if (false === $idIndex || false === $codbdgelIndex) {
                continue;
            }

            $id = $values[$idIndex] ?? '';
            $codbdgel = $values[$codbdgelIndex] ?? '';
            $codeArea = substr($codbdgel, 5, 2);

            if (0 === strpos($codbdgel, '08') && 'AA' === $codeArea) {
                $this->inventarioData[$id] = ['codbdgel' => $codbdgel];
            }
        }

        return $this->inventarioData;
    }

    /**
     * Parses tb_funcional.sql.gz, accumulates per (id, cdfgr) `importe` across all
     * `id === idente` rows whose id appears in the inventario, then writes one Budget
     * row per (year, cdfgr, municipality).
     */
    private function importFuncional(int $year, array $inventarioData): void
    {
        $url = "https://raw.githubusercontent.com/PopulateTools/gobierto-budgets-data/master/data/presupuestos_municipales/{$year}/planned/tb_funcional.sql.gz";
        $sql = $this->fetchAndExtractSql($url);
        $sql = str_replace('"', '`', $sql);

        $funcionalData = [];

        foreach (explode(';', $sql) as $statement) {
            $statement = trim($statement);
            if ('' === $statement) {
                continue;
            }

            if (!preg_match('/INSERT INTO `tb_funcional` \((.*?)\) VALUES \((.*?)\)/i', $statement, $matches)) {
                continue;
            }

            $columns = array_map('trim', explode(',', str_replace('`', '', $matches[1])));
            $values = array_map(fn ($v) => trim($v, " '"), explode(',', $matches[2]));

            $idIndex = array_search('id', $columns);
            $identeIndex = array_search('idente', $columns);
            $cdfgrIndex = array_search('cdfgr', $columns);
            $importeIndex = array_search('importe', $columns);

            if (false === $idIndex || false === $cdfgrIndex || false === $importeIndex) {
                continue;
            }

            $id = $values[$idIndex] ?? '';
            $idente = $values[$identeIndex] ?? '';
            $cdfgr = trim($values[$cdfgrIndex] ?? '', " '");

            if ($id !== $idente || !isset($inventarioData[$id])) {
                continue;
            }

            $importe = (float) ($values[$importeIndex] ?? 0);
            $funcionalData[$id][$cdfgr] = ($funcionalData[$id][$cdfgr] ?? 0) + $importe;
        }

        $this->persistFuncional($funcionalData);
    }

    private function persistFuncional(array $funcionalData): void
    {
        foreach ($funcionalData as $id => $cdfgrs) {
            $munCode = substr($this->inventarioData[$id]['codbdgel'], 0, 5);
            $municipality = $this->geo->getMunicipalityByCode($munCode);

            foreach ($cdfgrs as $cdfgr => $importe) {
                $this->setBudgetEntry($this->year, $importe, $cdfgr, $municipality);
            }

            $this->entityManager->flush();
        }
    }

    private function setBudgetEntry(int $year, float|int|string $value, string $program, ?object $municipality): void
    {
        if (!$municipality) {
            return;
        }

        $entry = $this->entityManager->getRepository(Budget::class)->findOneBy([
            'year' => $year,
            'program' => $program,
            'municipality' => $municipality,
        ]);

        if (!$entry) {
            $entry = new Budget();
            $entry->setYear($year);
            $entry->setProgram($program);
            $entry->setMunicipality($municipality);
        }

        $entry->setValue(EtlUtils::toFloat($value));

        $this->entityManager->persist($entry);
    }
}
