<?php

namespace App\Service\Etl\Importer;

use App\Entity\Indicator;
use App\Service\Etl\Dto\EtlContext;
use App\Service\Etl\Dto\IndicatorDefinition;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Migrates transparency portal indicators from local CSV files.
 *
 * Indicators:
 *   16.10.1 — Actualització portals transparència
 *              11 sub-scores (0–3) normalised to 0–1 each, averaged × 10.
 *              Municipality values only (no comarca/province).
 *   16.7.2  — Actualització canals participació
 *              Binary 0/1 from canals_participacio_act field.
 *              Municipality values only.
 *
 * Year hardcoded to 2023 (matching the CSV data vintage).
 */
final class Csv1610Importer extends AbstractEtlImporter
{
    private string $uploadDir;

    public function __construct(
        EntityManagerInterface $em,
        HttpClientInterface $http,
        LoggerInterface $logger,
        \App\Service\Etl\Geo\GeoRegistry $geo,
        \App\Service\Etl\Indicator\IndicatorFactory $indicatorFactory,
        \App\Service\Etl\Persistence\ValuePersister $values,
        \App\Service\Etl\Source\IdescatJsonClient $idescatJson,
        \App\Service\Etl\Source\IdescatTableClient $idescatTable,
        \App\Service\Etl\Source\DoClient $do,
        \App\Service\AggregationCalculatorService $aggregations,
        string $uploadDir,
    ) {
        parent::__construct($em, $http, $logger, $geo, $indicatorFactory, $values, $idescatJson, $idescatTable, $do, $aggregations);
        $this->uploadDir = $uploadDir;
    }

    protected function getDefinitions(): array
    {
        return [
            '16.10.1' => new IndicatorDefinition(
                indicatorId: '16.10.1',
                targetId: '16.10',
                targetName: "Garantir l'accés públic a la informació i protegir les llibertats fonamentals.",
                sdg: 16,
                indicatorName: 'Actualització portals transparència',
                indicatorDescription: "Grau d'actualització (0-10) de la documentació publicada al conjunt d'apartats del portal de transparència municipal",
                sign: true,
                source: 'CSV',
                unit: 'percent',
                scale: 1,
                url: '16.10.1.csv',
            ),
            '16.7.2' => new IndicatorDefinition(
                indicatorId: '16.7.2',
                targetId: '16.7',
                targetName: "Garantir l'adopció de decisions inclusives, participatives i representatives que responguin a les necessitats de la ciutadania.",
                sdg: 16,
                indicatorName: 'Actualització canals participació',
                indicatorDescription: "Indicador binari (0 No actualitzat; 1 Actualitzat) relatiu a l'actualització dels canals de participació ciutadana disponibles al Portal de transparència municipal.",
                sign: true,
                source: 'CSV',
                unit: 'percent',
                scale: 1,
                url: '16.7.2.csv',
            ),
        ];
    }

    protected function import(IndicatorDefinition $def, Indicator $indicator, EtlContext $context): void
    {
        $filePath = $this->uploadDir.'/'.$def->url;
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException(sprintf('File "%s" does not exist.', $filePath));
        }

        $handle = fopen($filePath, 'r');
        $header = fgetcsv($handle);

        $year = 2023;

        while (($row = fgetcsv($handle)) !== false) {
            $data = array_combine($header, $row);
            if (false === $data) {
                continue;
            }

            $token = substr($data['token'], 0, 5);
            $municipality = $this->getMunicipalityByCode($token);
            if (!$municipality) {
                continue;
            }

            $value = match ($def->indicatorId) {
                '16.10.1' => $this->computeTransparencyScore($data),
                '16.7.2' => (float) substr($data['canals_participacio_act'], 0, 1),
                default => null,
            };

            if (null === $value) {
                continue;
            }

            $this->setMunicipalityValue($def, $indicator, $municipality, $year, $value);
        }

        fclose($handle);

        $this->em->flush();
    }

    /**
     * Normalise 11 transparency sub-scores to [0,1] then return average × 10.
     * Max values per field: informacio_general=2, organs_de_govern=3,
     * organismes_depenents=2, organismes_amb_representacio=1,
     * canals_participacio=1, estructura_administrativa=3, serveis_publics=3,
     * informacio_economica=3, contractacio=1, planificacio=3, grups_interes=1.
     */
    private function computeTransparencyScore(array $data): float
    {
        $scores = [
            ((int) substr($data['informacio_general_act'], 0, 1)) / 2,
            ((int) substr($data['organs_de_govern_act'], 0, 1)) / 3,
            ((int) substr($data['organismes_depenents_act'], 0, 1)) / 2,
            ((int) substr($data['organismes_amb_representacio_act'], 0, 1)) / 1,
            ((int) substr($data['canals_participacio_act'], 0, 1)) / 1,
            ((int) substr($data['estructura_administrativa_act'], 0, 1)) / 3,
            ((int) substr($data['serveis_publics_act'], 0, 1)) / 3,
            ((int) substr($data['informacio_economica_act'], 0, 1)) / 3,
            ((int) substr($data['contractacio_act'], 0, 1)) / 1,
            ((int) substr($data['planificacio_act'], 0, 1)) / 3,
            ((int) substr($data['grups_interes_act'], 0, 1)) / 1,
        ];

        return array_sum($scores) * 10 / 11;
    }
}
