<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use App\Repository\ProvinceValueRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;

#[ORM\Entity(repositoryClass: ProvinceValueRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    // normalizationContext: ['groups' => ['municipality_value']],
    paginationEnabled: false,
    operations: [
        // new Get(),
        new GetCollection(),
    ]
)]
#[ApiFilter(SearchFilter::class, properties: [
    'indicator.target.target_id' => 'exact',
    'indicator.indicator_id' => 'exact',
    'indicator.id' => 'exact',
    'year' => 'exact'])]
class ProvinceValue
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'provinceValues')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Province $province = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $sdg = null;

    #[ORM\ManyToOne(inversedBy: 'provinceValues')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Indicator $indicator = null;

    #[ORM\Column]
    private ?float $value = null;

    #[ORM\Column(nullable: true)]
    private ?float $value2 = null;

    #[ORM\Column(nullable: true)]
    private ?int $subindicator = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $year = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $month = null;

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

    public function getProvince(): ?Province
    {
        return $this->province;
    }

    public function setProvince(?Province $province): static
    {
        $this->province = $province;

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

    public function getSubindicator(): ?float
    {
        return $this->subindicator;
    }

    public function setSubindicator(?int $value): static
    {
        $this->subindicator = $value;

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
