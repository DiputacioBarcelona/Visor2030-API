<?php

namespace App\Service;

use App\Repository\MunicipalityValueRepository;
use App\Repository\LabelRepository;
use App\Util\IndicatorCalculator;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Entity\Indicator;
use Symfony\Component\HttpFoundation\Response;

class CsvExportService
{
    private MunicipalityValueRepository $repository;
    private LabelRepository $labelRepository;
    private Filesystem $filesystem;

    public function __construct(MunicipalityValueRepository $repository, Filesystem $filesystem, LabelRepository $labelRepository)
    {
        $this->repository = $repository;
        $this->filesystem = $filesystem;
        $this->labelRepository = $labelRepository;
    }

    public function generateCsv(string $sdg, ?string $outputPath = null, ?string $municipality = null, ?string $year = null): string
    {
        // if $sdg is in the form X.X.X then we find by indicator, otherwise we find by SDG
        if (strpos($sdg, '.') !== false) {
            $data = $this->repository->findByIndicator($sdg, $municipality, $year);
        } else {
            $data = $this->repository->findBySdg($sdg, $municipality, $year);
        }

        if (empty($data)) {
            throw new \Exception('No data found for ID: '.$sdg);
        }

        $labels = $this->labelRepository->getHierarchyByLanguage('ca');

        $csvContent = $this->convertToCsv($data, $labels);

        if ($outputPath) {
            $this->filesystem->dumpFile($outputPath, $csvContent);

            return $outputPath;
        }

        return $csvContent;
    }

    private function convertToCsv(array $data, array $labels): string
    {
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, ['ods', 'fita', 'codi_indicador', 'subindicador', 'nom_indicador', 'any', 'codi_municipi', 'nom_municipi', 'valor_numerador','unitat_numerador', 'valor_denominador', 'unitat_denominador', 'valor_final', 'unitat_final']);

        foreach ($data as $row) {
            $indicatorId = $row->getIndicator()->getIndicatorId();
            $calculation = IndicatorCalculator::INDICATORS[$indicatorId]['calculation'];
            $valor_final = call_user_func($calculation, $row);

            // split sdg, target and indicator from $indicatorId
            $parts = explode('.', $indicatorId);
            $sdg = $parts[0];
            $target = $parts[1];
            $indicator = $parts[2];

            fputcsv($handle, [
                // $row->getId(),
                $sdg,
                $row->getIndicator()->getTarget()->getTargetId(),
                $indicatorId,
                $row->getSubindicator(),
                // $row->getIndicator()->getName(),
                $labels['SDGS'][$sdg]['TARGETS'][$target]['INDICATORS'][$indicator]['TITLE'],
                $row->getYear(),
                $row->getMunicipality()->getMunicipalityCode(),
                $row->getMunicipality()->getMunicipalityName(),

                $row->getValue(),
                isset($labels['SDGS'][$sdg]['TARGETS'][$target]['INDICATORS'][$indicator]['VALUES']['1']) ? $labels['SDGS'][$sdg]['TARGETS'][$target]['INDICATORS'][$indicator]['VALUES']['1']['UNIT'] : "",
                $row->getValue2(),
                isset($labels['SDGS'][$sdg]['TARGETS'][$target]['INDICATORS'][$indicator]['VALUES']['2']) ? $labels['SDGS'][$sdg]['TARGETS'][$target]['INDICATORS'][$indicator]['VALUES']['2']['UNIT'] : "",
                $valor_final,
                $labels['SDGS'][$sdg]['TARGETS'][$target]['INDICATORS'][$indicator]['UNIT'],

                
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }

   public function generateIndicatorsCsv(array $indicators): Response
{

    $labels = $this->labelRepository->getHierarchyByLanguage('ca');

    // Use memory stream instead of direct output
    $handle = fopen('php://temp', 'r+');

    // Header row
    $headers = [
        'Indicador',
        'Nom sintètic',
        'Nom complet',
        'Fita',
        'Nom Fita',
        'ODS',
        'Nom ODS',
        'Font de dades i metodologia (format MarkDown)',
        'Unitat final',
        'Títol del numerador',
        'Unitat del numerador',
        'Títol del denominador',
        'Unitat del denominador',

        'Pes (dins de l\'ODS)',
        'Pes (dins de la dimensió)',
        'Càlcul',
        'Sentit'

    ];
    fputcsv($handle, $headers);

    usort($indicators, function ($a, $b) {
        $aParts = explode('.', $a->getIndicatorId());
        $bParts = explode('.', $b->getIndicatorId());

        // Convert each part to int for numeric comparison
        for ($i = 0; $i < 3; $i++) {
            $aVal = isset($aParts[$i]) ? (int)$aParts[$i] : 0;
            $bVal = isset($bParts[$i]) ? (int)$bParts[$i] : 0;

            if ($aVal < $bVal) return -1;
            if ($aVal > $bVal) return 1;
        }

        return 0;
    });

    // print_r($labels['SDGS']);

    foreach ($indicators as $indicator) {
        /** @var \App\Entity\Indicator $indicator */
        $targetId = $indicator->getTarget()?->getTargetId() ?? '';
       
        $parts = explode('.', $indicator->getIndicatorId());
        $indicatorId = $parts[2] ?? '';
        $target = $parts[1] ?? '';
        $sdg = $parts[0] ?? '';

        $indicatorName = $labels['SDGS'][$sdg]['TARGETS'][$target]['INDICATORS'][$indicatorId]['TITLE'] ?? $indicator->getName();
        $indicatorFullName = $labels['SDGS'][$sdg]['TARGETS'][$target]['INDICATORS'][$indicatorId]['DESCRIPTION'] ?? '';
        $targetName = $labels['SDGS'][$sdg]['TARGETS'][$target]['DESCRIPTION'] ?? '';
        $sdgName = $labels['SDGS'][$sdg]['TITLE'] ?? '';
        $source = $labels['SDGS'][$sdg]['TARGETS'][$target]['INDICATORS'][$indicatorId]['SOURCE'] ?? '';

        $finalUnit = $labels['SDGS'][$sdg]['TARGETS'][$target]['INDICATORS'][$indicatorId]['UNIT'] ?? '';
        $numeratorTitle = $labels['SDGS'][$sdg]['TARGETS'][$target]['INDICATORS'][$indicatorId]['VALUES']['1']['TITLE'] ?? '';
        $numeratorUnit = $labels['SDGS'][$sdg]['TARGETS'][$target]['INDICATORS'][$indicatorId]['VALUES']['1']['UNIT'] ?? '';
        $denominatorTitle = $labels['SDGS'][$sdg]['TARGETS'][$target]['INDICATORS'][$indicatorId]['VALUES']['2']['TITLE'] ?? '';
        $denominatorUnit = $labels['SDGS'][$sdg]['TARGETS'][$target]['INDICATORS'][$indicatorId]['VALUES']['2']['UNIT'] ?? '';

        $signName = $indicator->isSign() ? 'Positiu (més gran és millor)' : 'Negatiu (més petit és millor)';

        // simple, ratio or difference
        $calculationName = '';
        if ($indicator->getCalculation() === 'simple') {
            $calculationName = 'Simple (utilitza només el numerador)';
        } elseif ($indicator->getCalculation() === 'ratio') {
            $calculationName = 'Ràtio (divideix el numerador pel denominador)';
        } elseif ($indicator->getCalculation() === 'difference') {
            $calculationName = 'Diferència (resta denominador del numerador)';
        } 
        
        fputcsv($handle, [
            $indicator->getIndicatorId(),
            
            $indicatorName,
            $indicatorFullName,
            $targetId,
            $targetName,
            $sdg,
            $sdgName ,
            $source,
            $finalUnit,
            $numeratorTitle,
            $numeratorUnit,
            $denominatorTitle,
            $denominatorUnit,

            $indicator->getWeight(),
            $indicator->getDimensionWeight(),
            $calculationName,
            $signName
        ]);
    }

    rewind($handle);
    $csvContent = stream_get_contents($handle);
    fclose($handle);

    // return new Response($csvContent, 200, [
    //     'Content-Type' => 'text/plain',
    // ]);

    $response = new Response($csvContent);
    $response->headers->set('Content-Type', 'text/csv');
    $response->headers->set('Content-Disposition', 'attachment; filename="export_indicators.csv"');

    return $response;
}

}
