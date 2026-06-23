<?php

namespace App\Controller;

use App\Service\ETLService;
use App\Service\Etl\Dto\EtlContext;
use App\Service\Etl\Enum\ImportScope;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ETLController extends AbstractController
{
    private string $uploadDir;

    public function __construct(string $uploadDir)
    {
        $this->uploadDir = $uploadDir;
    }

    #[Route('/api/etl/{indicator_id}', name: 'etl', methods: ['POST'])]
    public function triggerETL(string $indicator_id, ETLService $service, Request $request): JsonResponse
    {
        try {
            $importer = $service->findImporterFor($indicator_id);

            if (!$importer) {
                return new JsonResponse(['error' => "Indicator $indicator_id not found"], Response::HTTP_NOT_FOUND);
            }

            $csvFilename  = null;
            $csvFilename2 = null;

            if ($importer && $this->importerNeedsCsv($importer)) {
                /** @var UploadedFile $file */
                $file = $request->files->get('file');

                if (!$file) {
                    return new JsonResponse(['error' => 'No file uploaded'], Response::HTTP_BAD_REQUEST);
                }

                if ('csv' !== $file->getClientOriginalExtension()) {
                    return new JsonResponse(['error' => 'Invalid file type. Only CSV files are allowed.'], Response::HTTP_BAD_REQUEST);
                }

                $csvFilename = uniqid() . '.csv';
                try {
                    $file->move($this->uploadDir, $csvFilename);
                } catch (FileException $e) {
                    return new JsonResponse(['error' => 'Failed to upload file: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
                }
            }

            $scopeParam = $request->query->get('scope');
            $scopes = $scopeParam ? ImportScope::fromCsv($scopeParam) : null;

            $context = new EtlContext(
                triggerType: 'api',
                userId:      $this->getUser()?->getId(),
                identifier:  $this->getUser()?->getUserIdentifier() ?? 'anonymous',
                scopes:      $scopes,
                csvFilename: $csvFilename,
                csvFilename2: $csvFilename2,
            );

            $service->run($indicator_id, $context);

            return new JsonResponse(['status' => "Indicator $indicator_id imported successfully"]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function importerNeedsCsv(object $importer): bool
    {
        // Future: check if importer extends an AbstractCsvImporter base.
        // For now CSV importers are still legacy services.
        return false;
    }
}
