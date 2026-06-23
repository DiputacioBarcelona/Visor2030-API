<?php

namespace App\Service;

use App\Service\Etl\Dto\EtlContext;
use App\Service\Etl\Importer\AbstractEtlImporter;

class ETLService
{
    public function __construct(private readonly iterable $importers) {}

    public function run(string $indicatorId, EtlContext $context): bool
    {
        $importer = $this->findImporterFor($indicatorId);
        if ($importer) {
            return $importer->run($indicatorId, $context);
        }

        throw new \Exception("Indicator $indicatorId not found");
    }

    public function findImporterFor(string $indicatorId): ?AbstractEtlImporter
    {
        foreach ($this->importers as $importer) {
            if ($importer->supports($indicatorId)) {
                return $importer;
            }
        }

        return null;
    }

    /** @return string[] */
    public function getAllSupportedIndicatorIds(): array
    {
        $ids = [];
        foreach ($this->importers as $importer) {
            foreach ($importer->getSupportedIndicatorIds() as $id) {
                if (!in_array($id, $ids, true)) {
                    $ids[] = $id;
                }
            }
        }
        sort($ids);

        return $ids;
    }
}
