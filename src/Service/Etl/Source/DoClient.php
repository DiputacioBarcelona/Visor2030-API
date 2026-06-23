<?php

namespace App\Service\Etl\Source;

use App\Entity\Comarca;
use App\Entity\Province;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Wraps the Transparència Catalunya open-data API (analisi.transparenciacatalunya.cat/resource/...).
 * Years come from a dataset's metadata; population endpoints aggregate from the historic
 * municipality-population dataset (x5sz-niat).
 */
class DoClient
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Extracts the year value from a DO dataset's index page. Different datasets name
     * the field differently — falls back through ['any', 'campanya', 'id_eleccio',
     * 'by_year_data_de_posada_en_servei', 'curs', 'by_year_data_publicacio_formalitzacio',
     * 'by_year_data_inscripcio', 'any_exercici'].
     */
    public function getYears(string $url): array
    {
        $response = $this->httpClient->request('GET', $url);
        $data_years = $response->toArray();

        return array_map(function ($year) {
            if (isset($year['any'])) {
                return $year['any'];
            }
            if (isset($year['campanya'])) {
                return $year['campanya'];
            }
            if (isset($year['id_eleccio'])) {
                return $year['id_eleccio'];
            }
            if (isset($year['by_year_data_de_posada_en_servei'])) {
                return $year['by_year_data_de_posada_en_servei'];
            }
            if (isset($year['curs'])) {
                return substr($year['curs'], 0, 4);
            }
            if (isset($year['by_year_data_publicacio_formalitzacio'])) {
                return substr($year['by_year_data_publicacio_formalitzacio'], 0, 4);
            }
            if (isset($year['by_year_data_inscripcio'])) {
                return substr($year['by_year_data_inscripcio'], 0, 4);
            }
            if (isset($year['any_exercici'])) {
                return $year['any_exercici'];
            }
        }, $data_years);
    }

    /**
     * Population by municipality for a given year, filtered to Barcelona province
     * (codi_10 starts with "08"). Code key is 6 digits unless $short_code is true (5).
     */
    public function getMunicipalityPopulationByYear(string|int $year, bool $short_code = false): array
    {
        $url = 'https://analisi.transparenciacatalunya.cat/resource/x5sz-niat.json?$query=SELECT%20%60codi_10%60%2C%20%60nom_ens%60%2C%20%60any%60%2C%20%60total%60%2C%20%60homes%60%2C%20%60dones%60%0AWHERE%20caseless_starts_with(%60codi_10%60%2C%20%2208%22)%20AND%20(%60any%60%20IN%20(%22[[[year]]]%22))%0AORDER%20BY%20%60any%60%20DESC%20NULL%20LAST';
        $url = str_replace('[[[year]]]', (string) $year, $url);

        $response = $this->httpClient->request('GET', $url);
        $data = $response->toArray();

        $population = [];
        foreach ($data as $muni) {
            $code = substr($muni['codi_10'], 0, $short_code ? 5 : 6);
            $population[$code] = $muni['total'];
        }

        return $population;
    }

    /** Aggregates getMunicipalityPopulationByYear up to comarca via the comarca→municipality DB relation. */
    public function getComarcaPopulationByYear(string|int $year): array
    {
        $by_municipis = $this->getMunicipalityPopulationByYear($year, true);

        $population = [];
        foreach ($this->entityManager->getRepository(Comarca::class)->findAll() as $comarca) {
            $total = 0;
            foreach ($comarca->getMunicipalities() as $municipality) {
                $code = $municipality->getMunicipalityCode();
                if (!isset($by_municipis[$code])) {
                    continue;
                }
                $total += (int) $by_municipis[$code];
            }
            $population[$comarca->getComarcaCode()] = $total;
        }

        return $population;
    }

    /** Aggregates getComarcaPopulationByYear up to province via the province→comarca DB relation. */
    public function getProvincePopulationByYear(string|int $year): array
    {
        $by_comarca = $this->getComarcaPopulationByYear($year);

        $population = [];
        foreach ($this->entityManager->getRepository(Province::class)->findAll() as $province) {
            $total = 0;
            foreach ($province->getComarcas() as $comarca) {
                $code = $comarca->getComarcaCode();
                if (!isset($by_comarca[$code])) {
                    continue;
                }
                $total += (int) $by_comarca[$code];
            }
            $population[$province->getProvinceCode()] = $total;
        }

        return $population;
    }

    /** Discovers the most recent year in the population dataset and returns it. */
    public function getMunicipalityPopulationLastYear(bool $short_code = false): array
    {
        $url = 'https://analisi.transparenciacatalunya.cat/resource/x5sz-niat.json?$query=SELECT%20%60any%60%20GROUP%20BY%20%60any%60%20ORDER%20BY%20%60any%60%20DESC%20NULL%20LAST%20LIMIT%201';

        $response = $this->httpClient->request('GET', $url);
        $data = $response->toArray();

        $year = $data[0]['any'];

        return $this->getMunicipalityPopulationByYear($year, $short_code);
    }

    /**
     * For indicator 2.1.1: agricultural hectares (sum_sup_neta_h) per municipality and
     * organic/conventional split (ccpae_e=S/N) for the given campaign year.
     *
     * Returns [$raw, $aggregatedByMunicipality] — $raw preserves the ccpae_e dimension
     * while $aggregatedByMunicipality sums organic + conventional per municipality.
     */
    public function getHectareesByYear(string|int $year): array
    {
        $url = 'https://analisi.transparenciacatalunya.cat/resource/si4p-ygat.json?$query=SELECT%0A%20%20%60id_mun%60%2C%0A%20%20%60ccpae_e%60%2C%0A%20%20%60campanya%60%2C%0A%20%20sum(%60sup_neta_h%60)%20AS%20%60sum_sup_neta_h%60%2C%0A%20%20%60nom_mun%60%0AWHERE%20caseless_eq(%60pro%60%2C%20%2208%22)%0AGROUP%20BY%20%60id_mun%60%2C%20%60ccpae_e%60%2C%20%60campanya%60%2C%20%60nom_mun%60%0AHAVING%20caseless_one_of(%60campanya%60%2C%20%22[[[year]]]%22)';
        $url = str_replace('[[[year]]]', (string) $year, $url);

        $response = $this->httpClient->request('GET', $url);
        $data = $response->toArray();

        $aggregated = [];
        foreach ($data as $row) {
            $municipi_code = $row['id_mun'];
            $value = $row['sum_sup_neta_h'];

            if (!isset($aggregated[$municipi_code])) {
                $aggregated[$municipi_code] = $value;
            } else {
                $aggregated[$municipi_code] += $value;
            }
        }

        return [$data, $aggregated];
    }
}
