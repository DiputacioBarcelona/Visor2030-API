<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use App\Repository\ComarcaValueRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;

#[ORM\Entity(repositoryClass: ComarcaValueRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    normalizationContext: ['groups' => ['comarca_value']],
    paginationEnabled: false,
    operations: [
        // new Get(),
        new GetCollection(),
    ]
)]
#[ApiFilter(SearchFilter::class, properties: [
    'comarca.id' => 'exact',
    'comarca.comarca_code' => 'exact',
    'indicator.target.target_id' => 'exact',
    'indicator.indicator_id' => 'exact',
    'indicator.id' => 'exact',
    'year' => 'exact'])]
class ComarcaValue
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'comarcaValues')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups('comarca_value')]
    private ?Comarca $comarca = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $sdg = null;

    #[ORM\ManyToOne(inversedBy: 'comarcaValues')]
    #[Groups(['comarca_value'])]
    private ?Indicator $indicator = null;

    #[ORM\Column]
    #[Groups(['comarca_value'])]
    private ?float $value = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['comarca_value'])]
    private ?float $value2 = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['comarca_value'])]
    private ?int $subindicator = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    #[Groups(['comarca_value'])]
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

    public function getComarca(): ?Comarca
    {
        return $this->comarca;
    }

    public function setComarca(?Comarca $comarca): static
    {
        $this->comarca = $comarca;

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
