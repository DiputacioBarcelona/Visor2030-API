<?php

namespace App\Service;

use App\Entity\Aggregation;
use App\Entity\Comarca;
use App\Entity\Municipality;
use App\Entity\Population;
use App\Entity\Province;
use App\Entity\Target;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;

class LoadMunicipalityDataService
{
    private const POPULATION_API_URL = 'https://analisi.transparenciacatalunya.cat/resource/x5sz-niat.json';
    private const POPULATION_BATCH_SIZE = 500;

    private $entityManager;
    private $httpClient;
    private $logger;

    public function __construct(EntityManagerInterface $entityManager, HttpClientInterface $httpClient, LoggerInterface $logger)
    {
        $this->entityManager = $entityManager;
        $this->httpClient = $httpClient;
        $this->logger = $logger;
    }

    // ── Aggregation helpers ──────────────────────────────────────────

    private function findOrCreateAggregation(string $slug, string $name, string $group): Aggregation
    {
        $aggregation = $this->entityManager->getRepository(Aggregation::class)
            ->findOneBy(['slug' => $slug]);

        if (!$aggregation) {
            $aggregation = new Aggregation();
            $aggregation->setSlug($slug);
            $aggregation->setName($name);
            $aggregation->setGroup($group);
            $this->entityManager->persist($aggregation);
            $this->entityManager->flush();
            $this->logger->info("Created aggregation: $name ($slug)");
        }

        return $aggregation;
    }

    private function clearAggregationsForSlugs(array $slugs): void
    {
        $conn = $this->entityManager->getConnection();
        $conn->executeStatement(
            'DELETE ma FROM municipality_aggregation ma
             JOIN aggregation a ON ma.aggregation_id = a.id
             WHERE a.slug IN (?)',
            [$slugs],
            [\Doctrine\DBAL\ArrayParameterType::STRING]
        );
        $this->logger->info('Cleared aggregation assignments for slugs: ' . implode(', ', $slugs));
    }

    // ── Municipalities ───────────────────────────────────────────────

    public function importMunicipalities(): void
    {
        // Fetch data from external API
        $response = $this->httpClient->request('GET', 'https://do.diba.cat/api/dataset/municipis/');
        $data = $response->toArray();
        $elements = $data['elements'];

        // $data['modificacio'] has format "2024-08-26 16:47:03". we just want the year
        $year = substr($data['modificacio'], 0, 4);

        // Process and store the data
        foreach ($elements as $item) {
            $this->logger->debug(print_r($item, true));

            $province = $this->getProvince($item['grup_provincia']);
            $comarca = $this->getComarca($item['grup_comarca'], $province);
            $municipality = $this->getMunicipality($item, $comarca, $year);
        }
    }

    private function getProvince($province_data): Province
    {
        $code = $province_data['provincia_codi'];
        $name = $province_data['provincia_nom'];

        $province = $this->entityManager->getRepository(Province::class)->findOneBy(['province_code' => $code]);
        if (!$province) {
            $province = new Province();
            $province->setProvinceName($name);
            $province->setProvinceCode($code);
            $this->entityManager->persist($province);
            $this->logger->debug("Created new province $name");
            $this->entityManager->flush();
        } else {
            $this->logger->debug("Found province $name");
        }

        return $province;
    }

    private function getComarca($comarca_data, $province): Comarca
    {
        $code = $comarca_data['comarca_codi'];
        $comarca_name = $comarca_data['comarca_nom'];

        $comarca = $this->entityManager->getRepository(Comarca::class)->findOneBy(['comarca_code' => $code]);
        if (!$comarca) {
            $comarca = new Comarca();
            $comarca->setComarcaName($comarca_name);
            $comarca->setComarcaCode($code);
            $comarca->setProvince($province);
            $this->entityManager->persist($comarca);
            $this->logger->debug("Created new comarca $comarca_name");
            $this->entityManager->flush();
        } else {
            $this->logger->debug("Found comarca $comarca_name");
        }

        return $comarca;
    }

    private function getMunicipality($municipi_data, $comarca, $year): Municipality
    {
        $code = $municipi_data['ine'];
        $code6 = $municipi_data['ine6'];
        $name = $municipi_data['municipi_nom'];
        $loc = $municipi_data['centre_municipal'];
        $pop = $municipi_data['nombre_habitants'];

        $municipi = $this->entityManager->getRepository(Municipality::class)->findOneBy(['municipality_code_6' => $code6]);
        if (!$municipi) {
            $municipi = new Municipality();
            $municipi->setMunicipalityName($name);
            $municipi->setMunicipalityCode($code);
            $municipi->setMunicipalityCode6($code6);
            $municipi->setLoc($loc);
            $municipi->setComarca($comarca);
            $municipi->setPopulation($pop);
        } else {
            $municipi->setComarca($comarca);
            $municipi->setPopulation($pop);
            $municipi->setPopulationYear($year);
        }

        $this->entityManager->persist($municipi);
        $this->entityManager->flush();

        return $municipi;
    }

    // ── Population (historical, from Transparència Catalunya API) ────

    public function importPopulation(): void
    {
        // Build a lookup of municipality_code_6 => Municipality entity
        $municipalities = $this->entityManager->getRepository(Municipality::class)->findAll();
        $municipalityMap = [];
        foreach ($municipalities as $municipality) {
            $municipalityMap[$municipality->getMunicipalityCode6()] = $municipality;
        }

        $this->logger->info(sprintf('Loaded %d municipalities from DB.', count($municipalityMap)));
        $this->logger->info('Fetching population data from API...');

        $offset = 0;
        $limit = 5000;
        $totalImported = 0;
        $totalUpdated = 0;
        $batchCount = 0;

        // Build index of existing Population records
        $existingPopulations = [];
        $existingRecords = $this->entityManager->getRepository(Population::class)->findAll();
        foreach ($existingRecords as $record) {
            $key = $record->getMunicipality()->getId() . '_' . $record->getYear();
            $existingPopulations[$key] = $record;
        }

        $this->logger->info(sprintf('Found %d existing population records.', count($existingPopulations)));

        do {
            $response = $this->httpClient->request('GET', self::POPULATION_API_URL, [
                'query' => [
                    '$select' => 'codi_10,any,total',
                    '$where' => "starts_with(codi_10,'08') AND starts_with(nom_ens,'Ajuntament')",
                    '$limit' => $limit,
                    '$offset' => $offset,
                    '$order' => 'codi_10,any',
                ],
            ]);

            $rows = $response->toArray();

            if (empty($rows)) {
                break;
            }

            foreach ($rows as $row) {
                $code6 = substr($row['codi_10'], 0, 6);
                $year = (int) $row['any'];
                $populationCount = (int) $row['total'];

                if (!isset($municipalityMap[$code6])) {
                    $this->logger->debug("No municipality found for code6: $code6");
                    continue;
                }

                $municipality = $municipalityMap[$code6];
                $key = $municipality->getId() . '_' . $year;

                if (isset($existingPopulations[$key])) {
                    $existingPopulations[$key]->setPopulationCount($populationCount);
                    ++$totalUpdated;
                } else {
                    $entry = new Population();
                    $entry->setMunicipality($municipality);
                    $entry->setYear($year);
                    $entry->setPopulationCount($populationCount);
                    $this->entityManager->persist($entry);
                    $existingPopulations[$key] = $entry;
                    ++$totalImported;
                }

                ++$batchCount;

                if ($batchCount % self::POPULATION_BATCH_SIZE === 0) {
                    $this->entityManager->flush();
                }
            }

            $offset += $limit;
            $this->logger->info(sprintf('  Fetched %d rows (offset %d)...', count($rows), $offset));
        } while (count($rows) === $limit);

        $this->entityManager->flush();

        $this->logger->info(sprintf('Done. Created %d, updated %d population records.', $totalImported, $totalUpdated));
    }

    // ── Targets ──────────────────────────────────────────────────────

    public function importTargets(): void
    {
        $file = fopen('public/uploads/targets.csv', 'r');
        if ($file) {
            while (($line = fgetcsv($file, 1000, ',')) !== false) {
                $id = $line[0];
                $name = $line[1];
                $sdg = (int) explode('.', $id)[0];

                $target = $this->entityManager->getRepository(Target::class)->findOneBy(['target_id' => $id]);
                if (!$target) {
                    $target = new Target();
                    $target->setTargetId($id);
                    $target->setTargetName($name);
                    $target->setSdg($sdg);
                    $this->logger->debug("Created new target $name");
                } else {
                    $target->setTargetName($name);
                    $this->logger->debug("Found target $name");
                }

                $this->entityManager->persist($target);
            }
            fclose($file);
            $this->entityManager->flush();
        } else {
            throw new \Exception('Could not open targets.csv file');
        }
    }

    // ── Ubicacio → Aggregation ───────────────────────────────────────

    public function importUbicacioData(): void
    {
        $this->clearAggregationsForSlugs(['litoral', 'muntanya', 'interior']);

        $litoral = $this->findOrCreateAggregation('litoral', 'Litoral', 'ubicacio');
        $muntanya = $this->findOrCreateAggregation('muntanya', 'Muntanya', 'ubicacio');
        $interior = $this->findOrCreateAggregation('interior', 'Interior', 'ubicacio');

        // Fetch data from Transparència Catalunya API
        $url = 'https://analisi.transparenciacatalunya.cat/resource/375x-2wzk.json?$query=SELECT%0A%20%20%60codi_ine%60%2C%0A%20%20%60municipi%60%2C%0A%20%20%60mbit_funcional%60%2C%0A%20%20%60micropobles%60%2C%0A%20%20%60muntanya%60%2C%0A%20%20%60litoral%60%0AWHERE%0A%20%20caseless_one_of(%60any%60%2C%20%222022%22)%0A%20%20AND%20caseless_one_of(%60prov_ncia%60%2C%20%22Barcelona%22)';

        $response = $this->httpClient->request('GET', $url);
        $data = $response->toArray();

        foreach ($data as $item) {
            $municipality = $this->entityManager->getRepository(Municipality::class)->findOneBy(['municipality_code' => $item['codi_ine']]);
            if ($municipality) {
                if ($item['litoral'] === 'Sí') {
                    $municipality->addAggregation($litoral);
                } elseif ($item['muntanya'] === 'Sí') {
                    $municipality->addAggregation($muntanya);
                } else {
                    $municipality->addAggregation($interior);
                }
                $this->entityManager->persist($municipality);
            } else {
                $this->logger->warning("Could not find municipality with code {$item['codi_ine']} to set ubicacio");
            }

            $this->entityManager->flush();
        }
    }

    // ── Ruralitat → Aggregation ──────────────────────────────────────

    public function importRuralitatData(): void
    {
        $this->clearAggregationsForSlugs(['rural', 'rural-especial-atencio', 'incorporat', 'no-rural']);

        $aggMap = [
            'Rural'     => $this->findOrCreateAggregation('rural', 'Rural', 'ruralitat'),
            'Rural EA'  => $this->findOrCreateAggregation('rural-especial-atencio', 'Rural especial atenció', 'ruralitat'),
            'Incorporat' => $this->findOrCreateAggregation('incorporat', 'Incorporat', 'ruralitat'),
            'No rural'  => $this->findOrCreateAggregation('no-rural', 'No rural', 'ruralitat'),
        ];

        $file = fopen('public/uploads/rurals.csv', 'r');
        if ($file) {
            while (($line = fgetcsv($file, 1000, ',')) !== false) {
                $id = $line[1];
                $ruralitat_name = $line[4];

                $municipality = $this->entityManager->getRepository(Municipality::class)->findOneBy(['municipality_code' => $id]);
                if ($municipality) {
                    if (isset($aggMap[$ruralitat_name])) {
                        $municipality->addAggregation($aggMap[$ruralitat_name]);
                    }
                    $this->entityManager->persist($municipality);
                } else {
                    $this->logger->warning("Could not find municipality with code $id to set ruralitat");
                }
            }

            fclose($file);
            $this->entityManager->flush();
        } else {
            throw new \Exception('Could not open rurals.csv file');
        }
    }

    // ── Territory + AMB/RMB → Aggregation ────────────────────────────

    public function importTerritoryData(): array
    {
        $this->clearAggregationsForSlugs(['valles', 'penedes', 'comarques-centrals', 'amb', 'rmb']);

        $agg_valles = $this->findOrCreateAggregation('valles', 'Vallès', 'territorial-region');
        $agg_penedes = $this->findOrCreateAggregation('penedes', 'Penedès', 'territorial-region');
        $agg_centrals = $this->findOrCreateAggregation('comarques-centrals', 'Comarques Centrals', 'territorial-region');
        $agg_amb = $this->findOrCreateAggregation('amb', 'AMB', 'regional-flag');
        $agg_rmb = $this->findOrCreateAggregation('rmb', 'RMB', 'regional-flag');

        // Fetch idescat tables for RMB, Comarques Centrals, Penedès, Vallès
        $today = date('d-m-Y');

        $url_source_1 = "https://www.idescat.cat/codis/?t=[[[dd-mm-yyyy]]]&id=50&n=28&f=ssv";
        $url_1 = str_replace('[[[dd-mm-yyyy]]]', $today, $url_source_1);
        $table_data_1 = $this->getMunicipalitiesFromIdescatTable($url_1);
        $rmb_municipalities = $table_data_1['is_in_rmb'] ?? [];
        $com_centrals_municipalities = $table_data_1['com_centrals'] ?? [];
        $penedes_municipalities = $table_data_1['penedes'] ?? [];

        $url_source_2 = "https://www.idescat.cat/codis/?t=[[[dd-mm-yyyy]]]&id=50&n=10&f=ssv";
        $url_2 = str_replace('[[[dd-mm-yyyy]]]', $today, $url_source_2);
        $table_data_2 = $this->getMunicipalitiesFromIdescatTable($url_2);
        $valles_municipalities = $table_data_2['valles'] ?? [];

        $imported = 0;
        $skipped = 0;

        // Import from CSV + cross-reference with idescat data
        $file = fopen('public/uploads/municipality_zones.csv', 'r');
        if ($file) {
            $firstRow = true;
            while (($line = fgetcsv($file, 1000, ',')) !== false) {
                if ($firstRow) {
                    $firstRow = false;
                    continue;
                }

                $id = $line[1];
                $isInAmb = $line[2] === '1';

                $municipality = $this->entityManager->getRepository(Municipality::class)->findOneBy(['municipality_code' => $id]);
                if ($municipality) {
                    $isInRmb = isset($rmb_municipalities[$id]);

                    if ($isInAmb) {
                        $municipality->addAggregation($agg_amb);
                    }
                    if ($isInRmb) {
                        $municipality->addAggregation($agg_rmb);
                    }

                    $isComarquesCentrals = isset($com_centrals_municipalities[$id]);
                    $isPenedes = isset($penedes_municipalities[$id]);
                    $isValles = isset($valles_municipalities[$id]);

                    if ($isComarquesCentrals) {
                        $municipality->addAggregation($agg_centrals);
                    } elseif ($isPenedes) {
                        $municipality->addAggregation($agg_penedes);
                    } elseif ($isValles) {
                        $municipality->addAggregation($agg_valles);
                    }

                    $this->entityManager->persist($municipality);
                    ++$imported;
                } else {
                    $this->logger->warning("Could not find municipality with code $id to set the municipality zones");
                    ++$skipped;
                }
            }

            fclose($file);
            $this->entityManager->flush();
        } else {
            throw new \Exception('Could not open municipality_zones.csv file');
        }

        return ['imported' => $imported, 'skipped' => $skipped];
    }

    private function getMunicipalitiesFromIdescatTable($url)
    {
        try {
            $response = $this->httpClient->request('GET', $url);
            $csv = $response->getContent();
        } catch (HttpExceptionInterface $e) {
            $this->logger->error($e->getMessage());
            return [];
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            return [];
        }

        $lines = explode("\n", $csv);
        $data = [];
        $collect = false;
        $currentKey = null;

        foreach ($lines as $line) {
            $columns = str_getcsv($line, ';');

            if (count($columns) <= 1) {
                continue;
            }

            $nivell = trim($columns[0]);
            $codi = trim($columns[1]);

            if ($nivell === 'Àmbit territorial' && $codi === 'AT01') {
                $collect = true;
                $currentKey = 'is_in_rmb';
                $data[$currentKey] = [];
                continue;
            } elseif ($nivell === 'Àmbit territorial' && $codi === 'AT06') {
                $collect = true;
                $currentKey = 'com_centrals';
                $data[$currentKey] = [];
                continue;
            } elseif ($nivell === 'Àmbit territorial' && $codi === 'AT08') {
                $collect = true;
                $currentKey = 'penedes';
                $data[$currentKey] = [];
                continue;
            } elseif ($nivell === 'Comarca' && $codi === '40') {
                $collect = true;
                $currentKey = 'valles';
                $data[$currentKey] = [];
                continue;
            } elseif ($nivell === 'Comarca' && $codi === '41') {
                $collect = true;
                $currentKey = 'valles';
                continue;
            } elseif ($nivell === 'Àmbit territorial') {
                $collect = false;
            } elseif ($nivell === 'Comarca') {
                $collect = false;
            }

            if ($collect && $nivell === 'Municipi') {
                $codi = substr($codi, 0, -1); // mun code 5
                $data[$currentKey][$codi] = true;
            }
        }

        return $data;
    }

    // ── Industrial → Aggregation ─────────────────────────────────────

    public function importIndustrialData(): void
    {
        $this->clearAggregationsForSlugs(['industrials']);

        $industrials_agg = $this->findOrCreateAggregation('industrials', 'Municipis Industrials', 'regional-flag');

        try {
            // Fetch employees (assalariats)
            $urlAssalariats = "https://api.idescat.cat/rc/v1/afic/14983/1/json?geo=mun";
            $responseAssalariats = $this->httpClient->request('GET', $urlAssalariats);

            if ($responseAssalariats->getStatusCode() !== 200) {
                throw new \Exception('Failed to fetch assalariats: HTTP ' . $responseAssalariats->getStatusCode());
            }

            $dataAssalariats = json_decode($responseAssalariats->getContent());
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('JSON decode error for assalariats: ' . json_last_error_msg());
            }
            if (isset($dataAssalariats->error)) {
                throw new \Exception('Assalariats API error: ' . $dataAssalariats->error);
            }

            // Fetch self-employed (autonoms)
            $urlAutonoms = "https://api.idescat.cat/rc/v1/afic/14995/1/json?geo=mun";
            $responseAutonoms = $this->httpClient->request('GET', $urlAutonoms);

            if ($responseAutonoms->getStatusCode() !== 200) {
                throw new \Exception('Failed to fetch autonoms: HTTP ' . $responseAutonoms->getStatusCode());
            }

            $dataAutonoms = json_decode($responseAutonoms->getContent());
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('JSON decode error for autonoms: ' . json_last_error_msg());
            }
            if (isset($dataAutonoms->error)) {
                throw new \Exception('Autonoms API error: ' . $dataAutonoms->error);
            }

        } catch (\Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface $e) {
            $this->logger->error('HTTP transport error: ' . $e->getMessage());
            return;
        } catch (\Exception $e) {
            $this->logger->error('Error: ' . $e->getMessage());
            return;
        }

        // Càlcul del quocient de localització (QL):
        // – Pes de la indústria al municipi = llocs de treball industrials del municipi / llocs de treball totals del municipi
        // – Pes de la indústria a la província = llocs de treball industrials de la província / llocs de treball totals de la província
        // – QL = pes de la indústria al municipi / pes de la indústria a la província
        // Criteri de classificació:
        // – Municipi industrial: QL industrial igual o superior a 1,5
        // – Municipi no industrial: QL industrial inferior a 1,5

        $totalIndustrialWorkersPerMunicipality = [];
        $totalWorkersPerMunicipality = [];

        // Helper function for accumulating employees and self-employed workers.
        $accumulateWorkers = function (object $data, string $column, array &$accumulator): void {
            foreach ($data->data as $munCode) {
                if ($munCode->col === $column && str_starts_with($munCode->row, '08')) {
                    $code = substr($munCode->row, 0, 5);
                    $accumulator[$code] = ($accumulator[$code] ?? 0) + $munCode->value;
                }
            }
        };

        $accumulateWorkers($dataAssalariats, 'Indústria', $totalIndustrialWorkersPerMunicipality);
        $accumulateWorkers($dataAutonoms, 'Indústria', $totalIndustrialWorkersPerMunicipality);
        $accumulateWorkers($dataAssalariats, 'Total', $totalWorkersPerMunicipality);
        $accumulateWorkers($dataAutonoms, 'Total', $totalWorkersPerMunicipality);

        // Sum municipalities workers to get the province value
        $totalIndustrialProvinceWorkers = array_sum($totalIndustrialWorkersPerMunicipality);
        $totalProvinceWorkers = array_sum($totalWorkersPerMunicipality);
        $industryProvinceShare = $totalIndustrialProvinceWorkers / $totalProvinceWorkers;

        foreach ($totalIndustrialWorkersPerMunicipality as $munCode => $value) {
            $totalWorkers = $totalWorkersPerMunicipality[$munCode];
            $industryMunicipalityShare = $value / $totalWorkers;
            $isIndustrial = $industryMunicipalityShare / $industryProvinceShare >= 1.5;

            if ($isIndustrial) {
                $municipality = $this->entityManager->getRepository(Municipality::class)->findOneBy(['municipality_code' => $munCode]);
                if ($municipality) {
                    $municipality->addAggregation($industrials_agg);
                    $this->entityManager->persist($municipality);
                } else {
                    $this->logger->warning("Could not find municipality with code $munCode to set the industrial aggregation");
                }
            }
        }

        $this->entityManager->flush();
    }
}
