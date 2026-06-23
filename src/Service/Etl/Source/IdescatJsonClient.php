<?php

namespace App\Service\Etl\Source;

use App\Service\Etl\Util\EtlUtils;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Wraps the IDESCAT statistical JSON API (api.idescat.cat/taules/v2/.../data?...&f=json).
 * Methods return shaped arrays keyed by municipality / comarca / province code.
 */
class IdescatJsonClient
{
    private const MUN_FILTER = '&mun='.EtlUtils::BCN_MUNICIPALITY_FILTER;
    private const COM_FILTER = '&com='.EtlUtils::BCN_COMARCA_FILTER;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Returns available years. For datasets indexed by MONTH (e.g. 9.2.1) returns the
     * latest month per year ("2025M02", "2024M12", …) sorted descending. For YEAR-indexed
     * datasets returns plain years sorted descending.
     */
    public function getYears(string $url): array
    {
        $response = $this->httpClient->request('GET', $url);
        $data_years = $response->toArray();

        if (isset($data_years['dimension']['MONTH'])) {
            $months = $data_years['dimension']['MONTH']['category']['index'];
            $years = [];
            $current_year = null;
            foreach ($months as $month) {
                $year = substr($month, 0, 4);

                if (!$current_year) {
                    $current_year = $year;
                    $years[] = $month;
                }

                if ($year !== $current_year) {
                    $years[] = $month;
                    $current_year = $year;
                } else {
                    $years[count($years) - 1] = $month;
                }
            }

            rsort($years);

            return $years;
        }

        $years = $data_years['dimension']['YEAR']['category']['index'];
        rsort($years);

        return $years;
    }

    /** Returns the full month index (descending) for MONTH-indexed datasets. */
    public function getMonths(string $url): array
    {
        $response = $this->httpClient->request('GET', $url);
        $data_years = $response->toArray();

        $years = $data_years['dimension']['MONTH']['category']['index'];
        rsort($years);

        return $years;
    }

    /**
     * Returns muniCode → totalPopulation for the requested year, summing across the
     * given AGE dimension values. URL pattern depends on the year (post-2023 uses the
     * censph/540 dataset, pre-2014 uses pmh/1180/1063, otherwise pmh/1180/8078).
     */
    public function getMunicipalityPopulationByAges(string $year, string $ages, string $sex = 'total'): array
    {
        if ($year >= '2023') {
            $url = "https://api.idescat.cat/taules/v2/censph/540/19948/mun/data?SEX={$sex}&AGE=Y016_064&year={$year}".self::MUN_FILTER;
            $url_info = "https://api.idescat.cat/taules/v2/censph/540/19948/mun?SEX={$sex}&AGE=Y016_064".self::MUN_FILTER;
        } elseif ($year < 2014) {
            $url = "https://api.idescat.cat/taules/v2/pmh/1180/1063/mun/data?AGE={$ages}&SEX={$sex}&year={$year}".self::MUN_FILTER;
            $url_info = "https://api.idescat.cat/taules/v2/pmh/1180/1063/mun/?AGE={$ages}&SEX={$sex}".self::MUN_FILTER;
        } else {
            $url = "https://api.idescat.cat/taules/v2/pmh/1180/8078/mun/data?AGE={$ages}&SEX={$sex}&year={$year}".self::MUN_FILTER;
            $url_info = "https://api.idescat.cat/taules/v2/pmh/1180/8078/mun/?AGE={$ages}&SEX={$sex}".self::MUN_FILTER;
        }

        $years = $this->getYears($url_info);
        if (!in_array($year, $years)) {
            $this->logger->error("Year {$year} not found in IDESCAT for population data. Available years: ".implode(', ', $years));

            return [];
        }

        try {
            $response = $this->httpClient->request('GET', $url);
            $data = $response->toArray();
        } catch (HttpExceptionInterface|\Exception $e) {
            $this->logger->error($e->getMessage());

            return [];
        }

        $munis = $data['dimension']['MUN']['category']['index'];
        $ages = $data['dimension']['AGE']['category']['index'];

        $data_values = $data['value'];

        $population = [];
        $i = 0;
        foreach ($munis as $muni) {
            $total = 0;
            foreach ($ages as $age) {
                $total += $data_values[$i];
                ++$i;
            }
            $population[$muni] = $total;
        }

        return $population;
    }

    /** Comarca-level equivalent of getMunicipalityPopulationByAges. */
    public function getComarcaPopulationByAges(string $year, string $ages, string $sex = 'total'): array
    {
        if ($year >= '2023') {
            $url = "https://api.idescat.cat/taules/v2/censph/540/19948/com/data?SEX={$sex}&AGE=Y016_064&year={$year}".self::COM_FILTER;
            $url_info = "https://api.idescat.cat/taules/v2/censph/540/19948/com?SEX={$sex}&AGE=Y016_064".self::COM_FILTER;
        } elseif ($year < 2014) {
            $url = "https://api.idescat.cat/taules/v2/pmh/1180/1063/com/data?AGE={$ages}&SEX={$sex}&year={$year}".self::COM_FILTER;
            $url_info = "https://api.idescat.cat/taules/v2/pmh/1180/1063/com?AGE={$ages}&SEX={$sex}".self::COM_FILTER;
        } else {
            $url = "https://api.idescat.cat/taules/v2/pmh/1180/8078/com/data?AGE={$ages}&SEX={$sex}&year={$year}".self::COM_FILTER;
            $url_info = "https://api.idescat.cat/taules/v2/pmh/1180/8078/com?AGE={$ages}&SEX={$sex}".self::COM_FILTER;
        }

        $years = $this->getYears($url_info);
        if (!in_array($year, $years)) {
            $this->logger->error("Year {$year} not found in IDESCAT for population data. Available years: ".implode(', ', $years));

            return [];
        }

        try {
            $response = $this->httpClient->request('GET', $url);
            $data = $response->toArray();
        } catch (HttpExceptionInterface|\Exception $e) {
            $this->logger->error($e->getMessage());

            return [];
        }

        $munis = $data['dimension']['COM']['category']['index'];
        $ages = $data['dimension']['AGE']['category']['index'];

        $data_values = $data['value'];

        $population = [];
        $i = 0;
        foreach ($munis as $muni) {
            $total = 0;
            foreach ($ages as $age) {
                $total += $data_values[$i];
                ++$i;
            }

            if (str_starts_with($muni, '0')) {
                $muni = substr($muni, 1);
            }

            $population[$muni] = $total;
        }

        return $population;
    }

    /** Province-level equivalent of getMunicipalityPopulationByAges (Barcelona only). */
    public function getProvincePopulationByAges(string $year, string $ages, string $sex = 'total'): array
    {
        if ($year >= '2023') {
            $url = "https://api.idescat.cat/taules/v2/censph/540/19948/prov/data?SEX={$sex}&AGE=Y016_064&year={$year}&prov=08";
            $url_info = "https://api.idescat.cat/taules/v2/censph/540/19948/prov?SEX={$sex}&AGE=Y016_064&prov=08";
        } elseif ($year < 2014) {
            $url = "https://api.idescat.cat/taules/v2/pmh/1180/1063/prov/data?AGE={$ages}&SEX={$sex}&year={$year}&prov=08";
            $url_info = "https://api.idescat.cat/taules/v2/pmh/1180/1063/prov?AGE={$ages}&SEX={$sex}&prov=08";
        } else {
            $url = "https://api.idescat.cat/taules/v2/pmh/1180/8078/prov/data?AGE={$ages}&SEX={$sex}&year={$year}&prov=08";
            $url_info = "https://api.idescat.cat/taules/v2/pmh/1180/8078/prov?AGE={$ages}&SEX={$sex}&prov=08";
        }

        $years = $this->getYears($url_info);
        if (!in_array($year, $years)) {
            $this->logger->error("Year {$year} not found in IDESCAT for population data. Available years: ".implode(', ', $years));

            return [];
        }

        try {
            $response = $this->httpClient->request('GET', $url);
            $data = $response->toArray();
        } catch (HttpExceptionInterface|\Exception $e) {
            $this->logger->error($e->getMessage());

            return [];
        }

        $munis = $data['dimension']['PROV']['category']['index'];
        $ages = $data['dimension']['AGE']['category']['index'];

        $data_values = $data['value'];

        $population = [];
        $i = 0;
        foreach ($munis as $muni) {
            $total = 0;
            foreach ($ages as $age) {
                $total += $data_values[$i];
                ++$i;
            }

            if (str_starts_with($muni, '0')) {
                $muni = substr($muni, 1);
            }

            $population[$muni] = $total;
        }

        return $population;
    }

    /**
     * Returns code → annual-average affiliate count for the given year. Code length
     * depends on scope: 'MUN' = 6 digits, 'COM' and 'PROV' = without leading zero.
     * Averages across the months present in IDESCAT for that year (typically 4: M03,
     * M06, M09, M12).
     */
    public function getAffiliatesByYear(string $year, string $sex = 'total', string $scope = 'mun'): array
    {
        $scope = strtoupper($scope);

        if ('COM' === $scope) {
            $url = "https://api.idescat.cat/taules/v2/afi/8604/8704/com/data?SEX={$sex}&month=[[[MONTHS]]]".self::COM_FILTER;
            $url_info = "https://api.idescat.cat/taules/v2/afi/8604/8704/com?SEX={$sex}".self::COM_FILTER;
        } elseif ('PROV' === $scope) {
            $url = "https://api.idescat.cat/taules/v2/afi/8604/8704/prov/data?SEX={$sex}&month=[[[MONTHS]]]&prov=08";
            $url_info = "https://api.idescat.cat/taules/v2/afi/8604/8704/prov?SEX={$sex}&prov=08";
        } else {
            $url = "https://api.idescat.cat/taules/v2/afi/8604/8704/mun/data?SEX={$sex}&month=[[[MONTHS]]]".self::MUN_FILTER;
            $url_info = "https://api.idescat.cat/taules/v2/afi/8604/8704/mun?SEX={$sex}".self::MUN_FILTER;
        }

        $months = $this->getMonths($url_info);

        $years = array_unique(array_map(fn ($month) => substr($month, 0, 4), $months));

        if (!in_array($year, $years)) {
            $this->logger->error("Year {$year} not found in IDESCAT for affiliation data. Available years: ".implode(', ', $years));

            return [];
        }

        try {
            $months = array_filter($months, fn ($month) => substr($month, 0, 4) == $year);
            $url = str_replace('[[[MONTHS]]]', implode(',', $months), $url);

            $response = $this->httpClient->request('GET', $url);
            $data = $response->toArray();
        } catch (HttpExceptionInterface|\Exception $e) {
            $this->logger->error($e->getMessage());

            return [];
        }

        $months = $data['dimension']['MONTH']['category']['index'];
        $munis = $data['dimension'][$scope]['category']['index'];

        $data_values = $data['value'];

        $population = [];
        $i = 0;
        foreach ($months as $month) {
            foreach ($munis as $muni) {
                if ('PROV' === $scope || 'COM' === $scope) {
                    if (str_starts_with($muni, '0')) {
                        $muni = substr($muni, 1);
                    }
                }

                $value = $data_values[$i];
                ++$i;

                $population[$muni][] = $value;
            }
        }

        foreach ($population as $muni => $values) {
            $population[$muni] = array_sum($values) / count($values);
        }

        return $population;
    }
}
