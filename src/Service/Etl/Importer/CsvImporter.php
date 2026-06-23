<?php

namespace App\Service\Etl\Importer;

use App\Entity\Indicator;
use App\Service\Etl\Dto\EtlContext;
use App\Service\Etl\Dto\IndicatorDefinition;
use App\Service\Etl\Enum\ImportScope;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Handles all indicators whose data comes from static CSV files uploaded to
 * the server (public/uploads/ by default, configurable via UPLOAD_DIR env var).
 *
 * The CSV file name is stored in IndicatorDefinition::$url.
 * At runtime, the upload directory is prepended to locate the file.
 *
 * Per-indicator value extraction is handled inside extractValue().
 * Indicators that need a population denominator (value2) declare that
 * via extra['needsPopulation'] = true.
 */
final class CsvImporter extends AbstractEtlImporter
{
    // Barcelona municipalities with coastline — used to filter 14.2.1
    private const COAST_CODES = [
        '08110', '08261', '08163', '08035', '08235', '08040', '08006', '08032',
        '08264', '08197', '08121', '08029', '08219', '08172', '08118', '08126',
        '08015', '08194', '08019', '08169', '08301', '08089', '08056', '08270',
        '08307', '08074', '08231',
    ];

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
        private readonly string $uploadDir,
    ) {
        parent::__construct($em, $http, $logger, $geo, $indicatorFactory, $values, $idescatJson, $idescatTable, $do, $aggregations);
    }

    protected function getDefinitions(): array
    {
        return [
            '9.1.1' => new IndicatorDefinition(
                indicatorId: '9.1.1',
                targetId: '9.1',
                targetName: "Desenvolupar infraestructures sostenibles, resilients, de qualitat i amb elevada capacitat transformadora per tal de donar suport al desenvolupament econòmic del territori, amb especial atenció a l'accés assequible i equitatiu.",
                sdg: 9,
                indicatorName: 'Cobertura internet',
                indicatorDescription: 'Cobertura proporcionada per les xarxes fixes de, com a mínim, 100 Mbps, que comprèn les cobertures de HFC i FTTH',
                sign: true,
                source: 'CSV',
                unit: 'percent',
                scale: 100,
                url: '9.1.1.csv',
            ),
            '9.8.1' => new IndicatorDefinition(
                indicatorId: '9.8.1',
                targetId: '9.8',
                targetName: "Garantir l'accés universal i assequible a Internet i a les TIC al territori.",
                sdg: 9,
                indicatorName: 'Cobertura internet',
                indicatorDescription: 'Cobertura proporcionada per les xarxes fixes de, com a mínim, 100 Mbps, que comprèn les cobertures de HFC i FTTH',
                sign: true,
                source: 'CSV',
                unit: 'percent',
                scale: 100,
                url: '9.1.1.csv', // shares data file with 9.1.1
            ),
            '9.5.1' => new IndicatorDefinition(
                indicatorId: '9.5.1',
                targetId: '9.5',
                targetName: 'Augmentar la investigació científica i millorar la capacitat tecnològica dels sectors industrials.',
                sdg: 9,
                indicatorName: 'Empreses de coneixement',
                indicatorDescription: 'Empreses de coneixement',
                sign: true,
                source: 'CSV',
                unit: 'percent',
                scale: 1,
                url: '9.5.1.csv',
                extra: ['needsPopulation' => true],
            ),
            '13.1.1' => new IndicatorDefinition(
                indicatorId: '13.1.1',
                targetId: '13.1',
                targetName: "Enfortir la resiliència i la capacitat d'adaptació als riscos relacionats amb el clima i els desastres naturals.",
                sdg: 13,
                indicatorName: 'Vulnerabilitat a onades de calor',
                indicatorDescription: 'Índex propi de vulnerabilitat a les onades de calor i increment de la temperatura (Baixa, Mitja, Alta)',
                sign: true,
                source: 'CSV',
                unit: 'percent',
                scale: 100,
                url: '13.1.1.csv',
            ),
            '13.2.2' => new IndicatorDefinition(
                indicatorId: '13.2.2',
                targetId: '13.2',
                targetName: 'Incorporar mesures relatives al canvi climàtic en les polítiques, les estratègies i els plans locals.',
                sdg: 13,
                indicatorName: 'Intensitat emissions de gasos',
                indicatorDescription: "Relació entre les emissions de gasos d'efecte hivernacle i el consum d'energia (en kgCO2/kWh).",
                sign: true,
                source: 'CSV',
                unit: 'percent',
                scale: 1,
                url: '13.2.2.csv',
            ),
            '14.1.1' => new IndicatorDefinition(
                indicatorId: '14.1.1',
                targetId: '14.1',
                targetName: "Prevenir i reduir significativament la contaminació marina de tota mena i, en particular, l'ocasionada per activitats terrestres.",
                sdg: 14,
                indicatorName: '% Platges qualitat aigua excelent',
                indicatorDescription: "Percentatge de platges amb una qualitat de l'aigua de bany excel·lent respecte del total",
                sign: true,
                source: 'CSV',
                unit: 'percent',
                scale: 100,
                url: '14.1.1.csv',
            ),
            '14.2.1' => new IndicatorDefinition(
                indicatorId: '14.2.1',
                targetId: '14.2',
                targetName: 'Protegir i promoure la gestió sostenible dels ecosistemes marins i costaners.',
                sdg: 14,
                indicatorName: "% Franja costanera lliure d'urbanització",
                indicatorDescription: "Percentatge de la franja de 200 metres d'amplada de l'interior de la costa que no pertany a cap categoria de sòl improductiu artificial.",
                sign: true,
                source: 'CSV',
                unit: 'percent',
                scale: 100,
                url: '14.2.1.csv',
                extra: ['coastOnly' => true],
            ),
            '15.1.1' => new IndicatorDefinition(
                indicatorId: '15.1.1',
                targetId: '15.1',
                targetName: "Vetllar per la conservació, el restabliment i l'ús sostenible dels ecosistemes terrestres i d'aigua dolça, en particular els boscos, els aiguamolls, les muntanyes i les zones àrides.",
                sdg: 15,
                indicatorName: '% Superficie boscos',
                indicatorDescription: 'Superfície forestal en proporció a la superfície total',
                sign: true,
                source: 'CSV',
                unit: 'percent',
                scale: 1,
                url: '15.1.1.csv',
            ),
            '15.1.2' => new IndicatorDefinition(
                indicatorId: '15.1.2',
                targetId: '15.1',
                targetName: "Vetllar per la conservació, el restabliment i l'ús sostenible dels ecosistemes terrestres i d'aigua dolça, en particular els boscos, els aiguamolls, les muntanyes i les zones àrides.",
                sdg: 15,
                indicatorName: 'Superfície municipal amb figura de protecció i òrgan de gestió dels espais oberts',
                indicatorDescription: "Percentatge de la superfície municipal protegida per figures de legislació sectorial (ENPE'S, PEIN, Xarxa Natura 2000, altres espais de la XPN) i que compta amb un òrgan de gestió per a la conservació del patrimoni natural.",
                sign: true,
                source: 'CSV',
                unit: 'percent',
                scale: 1,
                url: '15.1.2.csv',
            ),
            '15.2.1' => new IndicatorDefinition(
                indicatorId: '15.2.1',
                targetId: '15.2',
                targetName: 'Augmentar el número de boscos amb gestió sostenible, posant fi a la desforestació, recuperant el boscos degradats i incrementant substancialment la repoblació forestal i la reforestació.',
                sdg: 15,
                indicatorName: 'Superfície municipal corresponent als espais oberts',
                indicatorDescription: "Percentatge de la superfície municipal protegida per figures de legislació sectorial (ENPE'S, PEIN, Xarxa Natura 2000, altres espais de la XPN)",
                sign: true,
                source: 'CSV',
                unit: 'percent',
                scale: 1,
                url: '15.2.1.csv',
            ),
            '16.6.1' => new IndicatorDefinition(
                indicatorId: '16.6.1',
                targetId: '16.6',
                targetName: 'Assolir institucions eficaces, responsables, transparents i que rendeixin comptes.',
                sdg: 16,
                indicatorName: 'Grau de maduresa digital',
                indicatorDescription: "Grau de transformació digital de l'ens local (0-100), fruit de l'anàlisi dels principals indicadors de govern digital i obert.",
                sign: true,
                source: 'CSV',
                unit: 'percent',
                scale: 1,
                url: '16.6.1.csv',
                extra: ['skipNd' => true],
            ),
            '16.7.2_HISTORIC' => new IndicatorDefinition(
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
                url: '16.7.2_HISTORIC.csv',
            ),
            '16.10.1_HISTORIC' => new IndicatorDefinition(
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
                url: '16.10.1_HISTORIC.csv',
            ),
            '17.2.1' => new IndicatorDefinition(
                indicatorId: '17.2.1',
                targetId: '17.2',
                targetName: 'Promoure la cooperació al desenvolupament i garantir el 0,7% dels ingressos en aquest àmbit.',
                sdg: 17,
                indicatorName: 'Pressupost destinat a ajuda oficial al desenvolupament',
                indicatorDescription: 'Pressupost destinat a ajuda oficial al desenvolupament',
                sign: true,
                source: 'CSV',
                unit: 'percent',
                scale: 1,
                url: '17.2.1.csv',
                extra: ['munByName' => true, 'needsPopulation' => true, 'fillMissing' => true],
            ),
            '5.5.1_HISTORIC' => new IndicatorDefinition(
                indicatorId: '5.5.1',
                targetId: '5.5',
                targetName: "Vetllar per la participació plena i efectiva de les dones, i per la igualtat d'oportunitats de lideratge en tots els àmbits de presa de decisions en la vida política, econòmica i pública.",
                sdg: 5,
                indicatorName: '% Càrrecs electes dones',
                indicatorDescription: '% Càrrecs electes del consistori municipal ocupats per dones',
                sign: true,
                source: 'DIBA',
                unit: 'percent',
                scale: 1,
                url: '5.5.1_HISTORIC.csv',
                extra: ['needsPopulation' => true, 'thousandsSep' => true],
            ),
            '2.1.1_HISTORIC' => new IndicatorDefinition(
                indicatorId: '2.1.1',
                targetId: '2.1',
                targetName: "Fomentar la productivitat agrícola i els ingressos de les persones que es dediquen a la producció d'aliments a petita escala.",
                sdg: 2,
                indicatorName: '% Superficie agricola ecològica',
                indicatorDescription: 'Superfície agrícola ecològica en relació a la superfície agrícola total',
                sign: true,
                source: 'DO',
                unit: 'ha',
                scale: 1,
                url: '2.1.1_HISTORIC.csv',
                extra: ['needsPopulation' => true, 'thousandsSep' => true],
            ),
            '1.2.3_HISTORIC' => new IndicatorDefinition(
                indicatorId: '1.2.3',
                targetId: '1.2',
                targetName: "Reduir la proporció de població que viu en la pobresa, augmentant els programes integrals que l'abordin en totes les seves dimensions.",
                sdg: 1,
                indicatorName: 'Nombre de socis de cooperatives per 1.000 habitants',
                indicatorDescription: 'Nombre de socis de cooperatives registrades per 1.000 habitants',
                sign: true,
                source: 'DO_DO',
                unit: 'abs',
                scale: 1,
                url: '1.2.3_HISTORIC.csv',
                extra: ['needsPopulation' => true, 'thousandsSep' => true],
            ),
        ];
    }

    protected function import(IndicatorDefinition $def, Indicator $indicator, EtlContext $context): void
    {
        if (!$this->shouldImport(ImportScope::Municipality)) {
            return;
        }

        // Allow a runtime CSV path from context (CLI --csv option), fall back to upload dir.
        $filePath = $context->csvPath ?? ($this->uploadDir.'/'.$def->url);

        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException(sprintf('CSV file not found: %s', $filePath));
        }

        $rows = $this->readCsv($filePath);

        $extra = $def->extra ?? [];

        // Pre-load populations per year for indicators that need them.
        $populationsByYear = [];
        if (!empty($extra['needsPopulation'])) {
            $years = array_unique(array_column($rows, 'any'));
            foreach ($years as $year) {
                $pop = $this->do->getMunicipalityPopulationByYear($year);
                $populationsByYear[$year] = $pop ?: $this->do->getMunicipalityPopulationLastYear();
            }
        }

        $seenYears = [];

        foreach ($rows as $row) {
            $year = $row['any'];

            // Municipality resolution
            $rawCode = $row['codi_municipi'];
            if (4 === strlen($rawCode)) {
                $rawCode = '0'.$rawCode;
            }

            if (!empty($extra['munByName'])) {
                $mun = $this->geo->getMunicipalityByName($rawCode);
            } else {
                $mun = $this->getMunicipalityByCode($rawCode);
            }

            if (!$mun) {
                continue;
            }

            $munCode6 = $mun->getMunicipalityCode6();

            // Coast filter for 14.2.1
            if (!empty($extra['coastOnly'])) {
                $munCode5 = $mun->getMunicipalityCode();
                if (!in_array($munCode5, self::COAST_CODES, true)) {
                    continue;
                }
            }

            $value = $this->extractValue($row['valor_final'], $def->indicatorId, $extra);
            if (null === $value) {
                continue;
            }

            $value2 = null;
            if (!empty($extra['needsPopulation'])) {
                $value2 = $populationsByYear[$year][$munCode6] ?? null;
                if (null === $value2) {
                    continue;
                }
            }

            $this->setMunicipalityValue($def, $indicator, $mun, (int) $year, (float) $value, null !== $value2 ? (float) $value2 : null);
            $seenYears[$year] = true;
        }

        $this->em->flush();

        // 17.2.1: fill municipalities missing from this dataset with 0
        if (!empty($extra['fillMissing'])) {
            foreach (array_keys($seenYears) as $year) {
                $pops = $populationsByYear[$year] ?? [];
                foreach ($this->em->getRepository(\App\Entity\MunicipalityValue::class)->findMunicipalitiesWithoutValue((int) $year, $indicator) as $missing) {
                    $pop = $pops[$missing->getMunicipalityCode6()] ?? null;
                    $this->setMunicipalityValue($def, $indicator, $missing, (int) $year, 0.0, null !== $pop ? (float) $pop : null);
                }
                $this->em->flush();
            }
        }
    }

    private function extractValue(string $raw, string $indicatorId, array $extra): float|int|string|null
    {
        if ('' === $raw) {
            return null;
        }

        // 13.1.1: categorical → numeric
        if ('13.1.1' === $indicatorId) {
            return match ($raw) {
                'Baixa' => 1,
                'Mitja' => 2,
                'Alta' => 3,
                'Molt Alta' => 4,
                default => null,
            };
        }

        // Skip ND values (16.6.1)
        if (!empty($extra['skipNd']) && in_array($raw, ['ND', 'N/A'], true)) {
            return null;
        }

        // Indicators that need thousands-separator removal before float conversion
        if (!empty($extra['thousandsSep'])) {
            $v = (float) str_replace(',', '.', str_replace('.', '', $raw));

            return 0.0 !== $v ? $v : null;
        }

        // 9.1.1, 9.8.1: skip zero/falsy
        if (in_array($indicatorId, ['9.1.1', '9.8.1'], true)) {
            $v = (float) str_replace(',', '.', $raw);

            return 0.0 !== $v ? $v : null;
        }

        // Default: normalize decimal separator
        return str_replace(',', '.', $raw);
    }

    // -------------------------------------------------------------------------

    private function readCsv(string $filePath): array
    {
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            throw new \RuntimeException(sprintf('Cannot open CSV file: %s', $filePath));
        }

        $header = fgetcsv($handle);
        $rows = [];

        while (($line = fgetcsv($handle)) !== false) {
            $data = array_combine($header, $line);
            if (false !== $data) {
                $rows[] = $data;
            }
        }

        fclose($handle);

        return $rows;
    }
}
