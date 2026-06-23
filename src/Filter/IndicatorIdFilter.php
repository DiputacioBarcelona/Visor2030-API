<?php

namespace App\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\PropertyInfo\Type;

class IndicatorIdFilter extends AbstractFilter
{
    /**
     * Passes a property through the filter.
     */
    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        // Only apply filter if the property is 'indicator_id'
        if ('indicator_id' !== $property || null === $value) {
            return;
        }

        // Join the 'Indicator' entity if not already joined
        if (!$this->isPropertyAlreadyJoined($property, $queryBuilder)) {
            $alias = $queryNameGenerator->generateJoinAlias('indicator');
            $queryBuilder->leftJoin(sprintf('%s.indicator', $queryBuilder->getRootAliases()[0]), $alias);
        }

        // Filter by indicator_id
        $queryBuilder
            ->andWhere('indicator.indicator_id = :indicator_id')
            ->setParameter('indicator_id', $value);
    }

    /**
     * Checks if the property is already joined in the query.
     */
    private function isPropertyAlreadyJoined(string $property, QueryBuilder $queryBuilder): bool
    {
        foreach ($queryBuilder->getDQLPart('join') as $joins) {
            foreach ($joins as $join) {
                if (false !== strpos($join->getAlias(), $property)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Returns the description of the filter.
     */
    public function getDescription(string $resourceClass): array
    {
        return [
            'indicator_id' => [
                'property' => 'indicator_id',
                'type' => Type::BUILTIN_TYPE_INT,
                'required' => false,
                'swagger' => [
                    'description' => 'Filter by indicator_id',
                    'name' => 'indicator_id',
                    'type' => 'integer',
                ],
            ],
        ];
    }
}
