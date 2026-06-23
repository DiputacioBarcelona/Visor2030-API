<?php

namespace App\Service\Etl\Indicator;

use App\Entity\Indicator;
use App\Entity\Target;
use App\Service\Etl\Dto\IndicatorDefinition;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Creates or fetches Target and Indicator entities from an IndicatorDefinition.
 * Persists new rows but does not flush — caller controls the transaction.
 */
class IndicatorFactory
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function getOrCreateTarget(IndicatorDefinition $def): Target
    {
        $target = $this->entityManager->getRepository(Target::class)->findOneBy(['target_id' => $def->targetId]);

        if (!$target) {
            $target = new Target();
            $target->setTargetId($def->targetId);
            $target->setTargetName($def->targetName);
            $target->setSdg($def->sdg);
            $this->entityManager->persist($target);
            $this->logger->debug("Created new target {$def->targetId}");
        }

        return $target;
    }

    public function getOrCreateIndicator(IndicatorDefinition $def, Target $target): Indicator
    {
        $indicator = $this->entityManager->getRepository(Indicator::class)->findOneBy([
            'indicator_id' => $def->indicatorId,
            'target' => $target,
        ]);

        if (!$indicator) {
            $indicator = new Indicator();
            $indicator->setIndicatorId($def->indicatorId);
            $indicator->setTarget($target);
            $indicator->setName($def->indicatorName);
            $indicator->setDescription($def->indicatorDescription);
            $indicator->setSign($def->sign);
            $indicator->setSource($def->source);
            $indicator->setApiUrlMunicipalities($def->url ?? '');
            $indicator->setUnit($def->unit);
            $indicator->setScale($def->scale);
            $indicator->setCalculation('simple');
            $this->entityManager->persist($indicator);
            $this->logger->debug("Created new indicator {$def->indicatorId} - {$def->indicatorName}");
        }

        return $indicator;
    }
}
