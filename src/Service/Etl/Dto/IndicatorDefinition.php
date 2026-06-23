<?php

namespace App\Service\Etl\Dto;

final class IndicatorDefinition
{
    public function __construct(
        public readonly string $indicatorId,
        public readonly string $targetId,
        public readonly string $targetName,
        public readonly int $sdg,
        public readonly string $indicatorName,
        public readonly string $indicatorDescription,
        public readonly bool $sign,
        public readonly string $source,
        public readonly string $unit,
        public readonly int|float $scale = 1,
        public readonly ?string $url = null,
        public readonly ?array $urls = null,
        public readonly ?string $urlInfo = null,
        public readonly ?string $urlComarca = null,
        public readonly ?string $urlProv = null,
        public readonly ?array $urlsInfo = null,
        public readonly array $extra = [],
    ) {
    }
}
