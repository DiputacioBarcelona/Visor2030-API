<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\AggregationValueRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: AggregationValueRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\Index(name: 'idx_agg_year_indicator', columns: ['year', 'indicator_id'])]
#[ORM\Index(name: 'idx_agg_subindicator', columns: ['subindicator'])]
#[ApiResource(
    normalizationContext: ['groups' => ['aggregation_value']],
    paginationEnabled: false,
    operations: [new GetCollection()]
)]
#[ApiFilter(SearchFilter::class, properties: [
    'aggregation' => 'exact',
    'aggregation.slug' => 'exact',
    'indicator' => 'exact',
    'indicator.indicator_id' => 'exact',
    'year' => 'exact',
])]
class AggregationValue
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'aggregationValues')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups('aggregation_value')]
    private ?Aggregation $aggregation = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups('aggregation_value')]
    private ?Indicator $indicator = null;

    #[ORM\Column]
    #[Groups('aggregation_value')]
    private ?float $value = null;

    #[ORM\Column(nullable: true)]
    #[Groups('aggregation_value')]
    private ?float $value2 = null;

    #[ORM\Column(nullable: true)]
    #[Groups('aggregation_value')]
    private ?int $subindicator = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    #[Groups('aggregation_value')]
    private ?int $year = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    #[Groups('aggregation_value')]
    private ?int $month = null;

    #[ORM\Column(length: 15)]
    #[Groups('aggregation_value')]
    private ?string $unit = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $sdg = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $createdAt;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $updatedAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAggregation(): ?Aggregation
    {
        return $this->aggregation;
    }

    public function setAggregation(?Aggregation $aggregation): static
    {
        $this->aggregation = $aggregation;

        return $this;
    }

    public function getIndicator(): ?Indicator
    {
        return $this->indicator;
    }

    public function setIndicator(?Indicator $indicator): static
    {
        $this->indicator = $indicator;

        return $this;
    }

    public function getValue(): ?float
    {
        return $this->value;
    }

    public function setValue(float $value): static
    {
        $this->value = $value;

        return $this;
    }

    public function getValue2(): ?float
    {
        return $this->value2;
    }

    public function setValue2(?float $value2): static
    {
        $this->value2 = $value2;

        return $this;
    }

    public function getSubindicator(): ?int
    {
        return $this->subindicator;
    }

    public function setSubindicator(?int $subindicator): static
    {
        $this->subindicator = $subindicator;

        return $this;
    }

    public function getYear(): ?int
    {
        return $this->year;
    }

    public function setYear(?int $year): static
    {
        $this->year = $year;

        return $this;
    }

    public function getMonth(): ?int
    {
        return $this->month;
    }

    public function setMonth(?int $month): static
    {
        $this->month = $month;

        return $this;
    }

    public function getUnit(): ?string
    {
        return $this->unit;
    }

    public function setUnit(?string $unit): static
    {
        $this->unit = $unit;

        return $this;
    }

    public function getSdg(): ?int
    {
        return $this->sdg;
    }

    public function setSdg(?int $sdg): static
    {
        $this->sdg = $sdg;

        return $this;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTime();
    }
}
