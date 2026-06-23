<?php

namespace App\Service\Etl\Dto;

use App\Service\Etl\Enum\ImportScope;

final class EtlContext
{
    /**
     * @param ImportScope[]|null $scopes null means all scopes (most permissive default)
     */
    public function __construct(
        public readonly string $triggerType,
        public readonly ?int $userId = null,
        public readonly string $identifier = 'system',
        public readonly ?array $scopes = null,
        public readonly ?string $csvFilename = null,
        public readonly ?string $csvFilename2 = null,
        public readonly bool $skipAggregation = false,
    ) {
    }

    public function hasScope(ImportScope $scope): bool
    {
        return null === $this->scopes || in_array($scope, $this->scopes, true);
    }
}
