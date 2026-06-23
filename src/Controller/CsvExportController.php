<?php

namespace App\Controller;

use App\Service\CsvExportService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\IndicatorRepository;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CsvExportController
{
    private CsvExportService $csvExportService;

    public function __construct(CsvExportService $csvExportService)
    {
        $this->csvExportService = $csvExportService;
    }

    #[Route('/export/csv', name: 'export_csv', methods: ['GET'])]
    public function exportCsv(Request $request): Response
    {
        $id = $request->query->get('id');
        $municipality = $request->query->get('municipality');
        $year = $request->query->get('year');

        if (!$id) {
            return new Response('ID parameter is required. Can be an SDG (1 to 17) or an indicator in the form X.X.X', Response::HTTP_BAD_REQUEST);
        }

        try {
            $csvContent = $this->csvExportService->generateCsv($id, null, $municipality, $year);

            $response = new Response($csvContent);
            $response->headers->set('Content-Type', 'text/csv');
            $response->headers->set('Content-Disposition', 'attachment; filename="export_'.$id.'.csv"');

            return $response;
        } catch (\Exception $e) {
            return new Response($e->getMessage(), Response::HTTP_NOT_FOUND);
        }
    }

    #[Route('/export/indicators', name: 'export_indicators_csv', methods: ['GET'])]
    public function exportIndicatorsCsv(
        CsvExportService $csvExportService,
        IndicatorRepository $indicatorRepository
    ): Response {
        $indicators = $indicatorRepository->findAll();

        return $csvExportService->generateIndicatorsCsv($indicators);
    }
}

