<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use App\Controller\MunicipalityValueController;
use App\Filter\IndicatorIdFilter;
use App\Repository\MunicipalityValueRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use ApiPlatform\Metadata\Get;

#[ORM\Entity(repositoryClass: MunicipalityValueRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    normalizationContext: ['groups' => ['municipality_value']],
    paginationEnabled: true,
    paginationItemsPerPage: 311,
    paginationClientItemsPerPage: true,
    operations: [
        // new Get(),
        new GetCollection(),
    ]
    // operations: [
    //     new GetCollection(
    //         uriTemplate: '/municipality_values/latest-by-comarca',
    //         controller: MunicipalityValueController::class,
    //         // extraProperties: [
    //         //     'openapi' => [
    //         //         'parameters' => [
    //         //             [
    //         //                 'name' => 'comarcaCode',
    //         //                 'in' => 'query',
    //         //                 'required' => true,
    //         //                 'schema' => ['type' => 'string'],
    //         //                 'description' => 'The code of the comarca to filter by.',
    //         //             ],
    //         //         ],
    //         //     ],
    //         // ]
    //     ),
    // ]
)]
#[ApiFilter(SearchFilter::class, properties: [
    'municipality.id' => 'exact',
    'municipality.municipality_code_6' => 'exact',
    'municipality.municipality_code' => 'exact',
    'municipality.comarca.comarca_code' => 'exact',
    'municipality.aggregations.slug' => 'exact',
    'indicator.target.target_id' => 'exact',
    'indicator.target.sdg' => 'exact',
    'indicator.indicator_id' => 'exact',
    'indicator.id' => 'exact',
    'year' => 'exact'])]
#[ApiFilter(OrderFilter::class, properties: ['year', 'value'])]
// #[ApiFilter(IndicatorIdFilter::class)]
// #[ORM\Index(name: 'idx_year', columns: ['year'])]
#[ORM\Index(name: 'idx_year_indicator', columns: ['year', 'indicator_id'])]
#[ORM\Index(name: 'idx_subindicator', columns: ['year', 'subindicator'])]
class MunicipalityValue
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'municipalityValues')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups('municipality_value')]
    private ?Municipality $municipality = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $sdg = null;

    #[ORM\ManyToOne(inversedBy: 'municipalityValues')]
    #[Groups(['municipality', 'municipality_value'])]
    private ?Indicator $indicator = null;

    #[ORM\Column]
    #[Groups(['municipality', 'indicator', 'municipality_value'])]
    private ?float $value = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['municipality', 'indicator', 'municipality_value'])]
    private ?float $value2 = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['municipality', 'indicator', 'municipality_value'])]
    private ?int $subindicator = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    #[Groups(['municipality', 'municipality_value'])]
    private ?int $year = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    #[Groups(['municipality'])]
    private ?int $month = null;

    #[ORM\Column(length: 15)]
    #[Groups(['municipality'])]
    private ?string $unit = null;

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

    public function getMunicipality(): ?Municipality
    {
        return $this->municipality;
    }

    public function setMunicipality(?Municipality $municipality): static
    {
        $this->municipality = $municipality;

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

    public function getUnit(): ?string
    {
        return $this->unit;
    }

    public function setUnit(?string $unit): static
    {
        $this->unit = $unit;

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
