<?php

namespace App\Service\Etl\Importer;

use App\Entity\Comarca;
use App\Entity\Indicator;
use App\Entity\MunicipalityValue;
use App\Entity\Province;
use App\Service\Etl\Dto\EtlContext;
use App\Service\Etl\Dto\IndicatorDefinition;
use App\Service\Etl\Enum\ImportScope;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Migrates indicator 7.1.1 — % Renda llars en energia.
 *
 * Numerator : per-municipality energy cost (DIBA ARCGIS ArcGIS API × local prices CSV)
 * Denominator: per-municipality gross household income (IDESCAT rfdbc)
 *
 * Both are expressed per capita, giving a ratio (energy spend / disposable income).
 *
 * Comarca and province values are population-weighted averages of the per-capita
 * municipality values (not a simple sum, because per-capita values must be
 * denormalised, aggregated, then renormalised by total population).
 */
final class Csv711Importer extends AbstractEtlImporter
{
    private const URL_YEARS = 'https://gissrv-test.diba.cat/arcgis/rest/services/SITAC/Dades_ODS/MapServer/1/query?where=1%3D1&outFields=ANY_CONSUM&returnDistinctValues=true&returnGeometry=false&f=json';
    private const URL_ENERGY = 'https://gissrv-test.diba.cat/arcgis/rest/services/SITAC/Dades_ODS/MapServer/1/query?where=ANY_CONSUM%3D[[[year]]]&outFields=*&returnGeometry=false&f=json';
    private const URL_RENDA = 'https://api.idescat.cat/taules/v2/rfdbc/13301/14148/mun/data?indicator=VALUE_EK&year=[[[year]]]&mun=080018,080023,080039,080044,080057,080060,080076,080082,080095,080109,080116,080121,080137,080142,080155,080168,080174,080180,080193,080207,080214,080229,080235,080240,080253,080266,080272,080288,080291,080305,080312,080327,080333,080348,080351,080364,080370,080386,080399,080403,080410,080425,080431,080446,080459,080462,080478,080484,080497,080500,080517,080522,080538,080543,080556,080569,080575,080581,080594,080608,080615,080620,080636,080641,080654,080667,080673,080689,080692,080706,080713,080728,080734,080749,080752,080765,080771,080787,080790,080804,080811,080826,080832,080847,080850,080863,080879,080885,080898,080902,080919,080924,080930,080945,080958,080961,080977,080983,080996,081000,081017,081022,081038,081043,081056,081069,081075,081081,081094,081108,081115,081120,081136,081141,081154,081167,081173,081189,081192,081206,081213,081228,081234,081249,081252,081265,081271,081287,081290,081304,081311,081326,081332,081347,081350,081363,081379,081385,081398,081402,081419,081424,081430,081445,081458,081461,081477,081483,081496,081509,081516,081521,081537,081542,081555,081568,081574,081580,081593,081607,081614,081629,081635,081640,081653,081666,081672,081688,081691,081705,081712,081727,081748,081751,081764,081770,081786,081799,081803,081810,081825,081831,081846,081859,081878,081884,081897,081901,081918,081923,081939,081944,081957,081960,081976,081982,081995,082009,082016,082021,082037,082042,082055,082068,082074,082080,082093,082107,082114,082129,082135,082140,082153,082166,082172,082188,082191,082205,082212,082227,082233,082248,082251,082264,082270,082286,082299,082303,082310,082325,082331,082346,082359,082362,082378,082384,082397,082401,082418,082423,082439,082444,082457,082460,082476,082482,082495,082508,082515,082520,082536,082541,082554,082567,082573,082589,082592,082606,082613,082628,082634,082649,082652,082665,082671,082687,082690,082704,082711,082726,082732,082747,082750,082763,082779,082785,082798,082802,082819,082824,082830,082845,082858,082861,082877,082883,082896,082900,082917,082922,082938,082943,082956,082969,082975,082981,082994,083008,083015,083020,083036,083041,083054,083067,083073,083089,089019,089024,089030,089045,089058';
    private const URL_RENDA_YEARS = 'https://api.idescat.cat/taules/v2/rfdbc/13301/14148/mun?indicator=VALUE_EK&mun=080018,080023,080039,080044,080057,080060,080076,080082,080095,080109,080116,080121,080137,080142,080155,080168,080174,080180,080193,080207,080214,080229,080235,080240,080253,080266,080272,080288,080291,080305,080312,080327,080333,080348,080351,080364,080370,080386,080399,080403,080410,080425,080431,080446,080459,080462,080478,080484,080497,080500,080517,080522,080538,080543,080556,080569,080575,080581,080594,080608,080615,080620,080636,080641,080654,080667,080673,080689,080692,080706,080713,080728,080734,080749,080752,080765,080771,080787,080790,080804,080811,080826,080832,080847,080850,080863,080879,080885,080898,080902,080919,080924,080930,080945,080958,080961,080977,080983,080996,081000,081017,081022,081038,081043,081056,081069,081075,081081,081094,081108,081115,081120,081136,081141,081154,081167,081173,081189,081192,081206,081213,081228,081234,081249,081252,081265,081271,081287,081290,081304,081311,081326,081332,081347,081350,081363,081379,081385,081398,081402,081419,081424,081430,081445,081458,081461,081477,081483,081496,081509,081516,081521,081537,081542,081555,081568,081574,081580,081593,081607,081614,081629,081635,081640,081653,081666,081672,081688,081691,081705,081712,081727,081748,081751,081764,081770,081786,081799,081803,081810,081825,081831,081846,081859,081878,081884,081897,081901,081918,081923,081939,081944,081957,081960,081976,081982,081995,082009,082016,082021,082037,082042,082055,082068,082074,082080,082093,082107,082114,082129,082135,082140,082153,082166,082172,082188,082191,082205,082212,082227,082233,082248,082251,082264,082270,082286,082299,082303,082310,082325,082331,082346,082359,082362,082378,082384,082397,082401,082418,082423,082439,082444,082457,082460,082476,082482,082495,082508,082515,082520,082536,082541,082554,082567,082573,082589,082592,082606,082613,082628,082634,082649,082652,082665,082671,082687,082690,082704,082711,082726,082732,082747,082750,082763,082779,082785,082798,082802,082819,082824,082830,082845,082858,082861,082877,082883,082896,082900,082917,082922,082938,082943,082956,082969,082975,082981,082994,083008,083015,083020,083036,083041,083054,083067,083073,083089,089019,089024,089030,089045,089058';

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
            '7.1.1' => new IndicatorDefinition(
                indicatorId: '7.1.1',
                targetId: '7.1',
                targetName: "Garantir l'accés universal a serveis d'energia assequibles, confiables i moderns, amb especial èmfasi en aquelles llars en situació de pobresa energètica.",
                sdg: 7,
                indicatorName: '% Renda llars en energia',
                indicatorDescription: "Cost (en percentatge) del consum final d'energia residencial (domèstica i transport) respecte de la Renda Familiar Disponible Bruta.",
                sign: true,
                source: 'CSV',
                unit: 'percent',
                scale: 100,
                url: '7.1.1.PREUS.csv',
            ),
        ];
    }

    protected function import(IndicatorDefinition $def, Indicator $indicator, EtlContext $context): void
    {
        // Intersect ARCGIS energy years with IDESCAT renda years
        $energyYears = $this->getEnergyYears();
        $rendaYears = $this->idescatJson->getYears(self::URL_RENDA_YEARS);
        $years = array_intersect($energyYears, $rendaYears);

        $prices = $this->loadPrices($def->url);

        foreach ($years as $year) {
            $populations = $this->do->getMunicipalityPopulationByYear($year);
            $rendes = $this->getRendaByYear((string) $year);

            if ($this->shouldImport(ImportScope::Municipality)) {
                $energyData = $this->http->request('GET', str_replace('[[[year]]]', $year, self::URL_ENERGY))->toArray();

                foreach ($energyData['features'] as $row) {
                    $attr = $row['attributes'];
                    $munCode = $attr['CODI_INE'] ?? null;
                    $municipality = $this->geo->getMunicipalityByCode($munCode);
                    if (!$municipality) {
                        continue;
                    }

                    $munCode6 = $municipality->getMunicipalityCode6();
                    $population = $populations[$munCode6] ?? null;
                    if (!$population) {
                        continue;
                    }

                    $energyCost = $this->computeEnergyCost($attr, $prices, $year);
                    $renda = $rendes[$munCode6] ?? null;
                    if (!$renda) {
                        continue;
                    }

                    $value = $energyCost / $population;
                    $value2 = $renda / $population;

                    $this->setMunicipalityValue($def, $indicator, $municipality, (int) $year, $value, $value2);
                }

                $this->em->flush();
            }

            if ($this->shouldImport(ImportScope::Comarca)) {
                $this->importComarcaWeighted((int) $year, $indicator, $populations);
            }

            if ($this->shouldImport(ImportScope::Province)) {
                $this->importProvinceWeighted((int) $year, $indicator, $populations);
            }

            $this->em->flush();
        }
    }

    /** Population-weighted comarca average of per-capita values. */
    private function importComarcaWeighted(int $year, Indicator $indicator, array $populations): void
    {
        $comarques = $this->em->getRepository(Comarca::class)->findAll();
        foreach ($comarques as $comarca) {
            $value = $value2 = $totalPop = 0.0;

            foreach ($comarca->getMunicipalities() as $municipality) {
                $entry = $this->em->getRepository(MunicipalityValue::class)->findOneBy([
                    'year' => $year, 'indicator' => $indicator, 'municipality' => $municipality,
                ]);
                if (!$entry) {
                    continue;
                }
                $pop = $populations[$municipality->getMunicipalityCode6()] ?? null;
                if (!$pop) {
                    continue;
                }
                $value += $entry->getValue() * $pop;
                $value2 += $entry->getValue2() * $pop;
                $totalPop += $pop;
            }

            if ($totalPop > 0 && ($value > 0 || $value2 > 0)) {
                $this->track($this->values->setComarcaValue($year, $indicator, $comarca, $value / $totalPop, $value2 / $totalPop));
            }
        }
        $this->em->flush();
    }

    /** Population-weighted province average of per-capita values. */
    private function importProvinceWeighted(int $year, Indicator $indicator, array $populations): void
    {
        $provinces = $this->em->getRepository(Province::class)->findAll();
        foreach ($provinces as $province) {
            $value = $value2 = $totalPop = 0.0;

            foreach ($province->getMunicipalities() as $municipality) {
                $entry = $this->em->getRepository(MunicipalityValue::class)->findOneBy([
                    'year' => $year, 'indicator' => $indicator, 'municipality' => $municipality,
                ]);
                if (!$entry) {
                    continue;
                }
                $pop = $populations[$municipality->getMunicipalityCode6()] ?? null;
                if (!$pop) {
                    continue;
                }
                $value += $entry->getValue() * $pop;
                $value2 += $entry->getValue2() * $pop;
                $totalPop += $pop;
            }

            if ($totalPop > 0 && ($value > 0 || $value2 > 0)) {
                $this->track($this->values->setProvinceValue($year, $indicator, $province, $value / $totalPop, $value2 / $totalPop));
            }
        }
        $this->em->flush();
    }

    private function getEnergyYears(): array
    {
        $data = $this->http->request('GET', self::URL_YEARS)->toArray();
        $years = array_map(fn ($f) => $f['attributes']['ANY_CONSUM'], $data['features']);
        $years = array_unique($years);
        rsort($years);

        return $years;
    }

    /** Fetch renda total (VALUE_EK × 1000) keyed by 6-digit municipality code. */
    private function getRendaByYear(string $year): array
    {
        $url = str_replace('[[[year]]]', $year, self::URL_RENDA);
        $data = $this->http->request('GET', $url)->toArray();
        $munis = array_values($data['dimension']['MUN']['category']['index']);
        $values = $data['value'];
        $result = [];
        foreach ($munis as $i => $mun) {
            $result[$mun] = ((float) ($values[$i] ?? 0)) * 1000;
        }

        return $result;
    }

    /**
     * Compute total energy cost for one municipality row by multiplying each
     * energy type's consumption (kWh equivalent) by the price for that year.
     */
    private function computeEnergyCost(array $attr, array $prices, int|string $year): float
    {
        return
            ((float) ($attr['GAS_NATURAL'] ?? 0)) * ((float) ($prices['gas_natural'][$year] ?? 0)) +
            ((float) ($attr['ELECTRICITAT'] ?? 0)) * ((float) ($prices['electricitat'][$year] ?? 0)) +
            ((float) ($attr['GASOIL_C'] ?? 0)) * ((float) ($prices['gasoil_c'][$year] ?? 0)) +
            ((float) ($attr['GLP'] ?? 0)) * ((float) ($prices['glp'][$year] ?? 0)) +
            ((float) ($attr['GASOIL_A'] ?? 0)) * ((float) ($prices['gasoil_a'][$year] ?? 0)) +
            ((float) ($attr['BIOMASSA'] ?? 0)) * ((float) ($prices['biomassa'][$year] ?? 0)) +
            ((float) ($attr['GASOLINA'] ?? 0)) * ((float) ($prices['benzina'][$year] ?? 0)) +
            ((float) ($attr['BIODIESEL'] ?? 0)) * ((float) ($prices['biodiesel'][$year] ?? 0));
    }

    /**
     * Load energy prices CSV. Returns nested array [energyType][year] => price.
     * CSV header row: €/kWh, 2005, 2006, ...
     */
    private function loadPrices(string $filename): array
    {
        $filePath = $this->uploadDir.'/'.$filename;
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException(sprintf('Prices file "%s" not found.', $filePath));
        }

        $handle = fopen($filePath, 'r');
        $header = fgetcsv($handle);
        $prices = [];
        while (($row = fgetcsv($handle)) !== false) {
            $data = array_combine($header, $row);
            if (false === $data) {
                continue;
            }
            $prices[$data['€/kWh']] = $data;
        }
        fclose($handle);

        return $prices;
    }
}
