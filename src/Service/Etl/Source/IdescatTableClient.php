<?php

namespace App\Service\Etl\Source;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Wraps the IDESCAT semicolon-separated table endpoints (f=ssv).
 *
 * These return a header block (free-text metadata terminated by an empty first column)
 * followed by a header row and a data section. Methods parse the data block into shaped
 * arrays.
 */
class IdescatTableClient
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Returns the year column from an IDESCAT SSV time-series table. Strips " (p)"
     * (provisional) markers and, if values are in "12/YYYY" form, keeps only December
     * rows and unwraps to YYYY.
     */
    public function getYears(string $url): array
    {
        $response = $this->httpClient->request('GET', $url);

        $csv = $response->getContent();
        $lines = explode("\n", $csv);
        $data = [];
        $start = false;

        foreach ($lines as $line) {
            $columns = str_getcsv($line, ';');
            if ($start) {
                if (count($columns) > 1) {
                    $data[] = ['year' => $columns[0]];
                }
            } elseif (empty($columns[0])) {
                $start = true;
            }
        }

        $result = array_map(function ($year) {
            $year['year'] = str_replace(' (p)', '', $year['year']);

            return $year['year'];
        }, $data);

        if (false !== strpos($result[0], '/')) {
            $result = array_filter($result, fn ($year) => 0 === strpos($year, '12/'));
            $result = array_map(fn ($year) => str_replace('12/', '', $year), $result);
        }

        $this->logger->error(print_r($result, true));

        return $result;
    }

    /**
     * Returns an associative array keyed by municipality code (first column) with each
     * value being a row keyed by the table header.
     * If $use_code_5 is true, truncates the key to the first 5 chars (handles IDESCAT
     * tables that include the verification digit as a 6th char).
     */
    public function getValues(string $url, bool $use_code_5 = false): array
    {
        try {
            $response = $this->httpClient->request('GET', $url);
            $csv = $response->getContent();
        } catch (HttpExceptionInterface|\Exception $e) {
            $this->logger->error($e->getMessage());

            return [];
        }

        $lines = explode("\n", $csv);
        $data = [];
        $start = false;
        $headers = [];

        foreach ($lines as $line) {
            $columns = str_getcsv($line, ';');

            if (count($columns) > 1) {
                if (!$start) {
                    $headers = $columns;
                    $start = true;
                } else {
                    $id = $use_code_5 ? substr($columns[0], 0, 5) : $columns[0];
                    if (count($headers) !== count($columns)) {
                        continue;
                    }
                    $data[$id] = array_combine($headers, $columns);
                }
            }
        }

        return $data;
    }

    /**
     * Province-table parser — keyed by year (the first column). Used for tables shaped
     * like "year ; metric1 ; metric2 ; …" rather than "code ; name ; …".
     */
    public function getProvinceValues(string $url): array
    {
        $response = $this->httpClient->request('GET', $url);

        $csv = $response->getContent();
        $lines = explode("\n", $csv);
        $data = [];
        $start = false;
        $headers = [];

        foreach ($lines as $line) {
            $columns = str_getcsv($line, ';');

            if (count($columns) > 1) {
                if (!$start) {
                    $headers = $columns;
                    $start = true;
                } else {
                    $year = $columns[0];
                    if (empty($headers[0])) {
                        $headers[0] = 'year';
                    }
                    $data[$year] = array_combine($headers, $columns);
                }
            }
        }

        print_r($data, true);

        return $data;
    }
}
