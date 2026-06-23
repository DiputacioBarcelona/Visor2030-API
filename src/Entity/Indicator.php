<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use App\Repository\IndicatorRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;

#[ORM\Entity(repositoryClass: IndicatorRepository::class)]
#[ApiResource(
    normalizationContext: ['groups' => ['indicator']],
    paginationEnabled: false,
    order: ['indicator_id' => 'ASC'],
    operations: [
        new Get(),
        new GetCollection(),
        new Patch(),
    ]
)]
// #[ApiResource(
//     normalizationContext: ['groups' => ['indicator:read']],
//     denormalizationContext: ['groups' => ['indicator:write']]
// )]
// #[ApiResource]
// #[ApiFilter(SearchFilter::class, properties: [
//     'sdg' => 'exact',
// ])]
#[ORM\Index(name: 'idx_weight', columns: ['weight'])]
#[ORM\Index(name: 'idx_dimension_weight', columns: ['dimension_weight'])]
#[ORM\Index(name: 'idx_indicator_id', columns: ['indicator_id'])]
class Indicator
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['municipality', 'municipality_value', 'target', 'indicator'])]
    private ?int $id = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $sdg = null;

    #[ORM\ManyToOne(inversedBy: 'indicators')]
    // #[ORM\JoinColumn(name: 'target_id', referencedColumnName: 'target_id')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['municipality', 'indicator'])]
    private ?Target $target = null;

    #[ORM\Column(length: 7, nullable: true)]
    #[Groups(['municipality', 'indicator', 'municipality_value', 'comarca_value', 'province_value', 'target'])]
    private ?string $indicator_id = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['municipality', 'indicator', 'municipality_value', 'comarca_value', 'province_value', 'target'])]
    private ?string $name = null;

    #[ORM\Column]
    #[Groups(['municipality', 'indicator', 'target'])]
    private ?bool $sign = null;

    #[ORM\Column(length: 15, nullable: true)]
    #[Groups(['municipality', 'indicator', 'target'])]
    private ?string $unit = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    #[Groups(['municipality', 'indicator'])]
    private ?int $scale = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['municipality', 'target'])]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups('municipality')]
    private ?string $source = null;

    #[ORM\Column(length: 4095, nullable: true)]
    private ?string $api_url_municipalities = null;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    #[Assert\Range(min: 0, max: 100)]
    #[Groups(['indicator', 'target'])]
    private int $weight = 0;

    #[ORM\Column(length: 255, options: ['default' => 'simple'])]
    #[Groups(['indicator', 'target'])]
    private ?string $calculation = null;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    #[Assert\Range(min: 0, max: 100)]
    #[Groups(['indicator', 'target'])]
    private int $dimension_weight = 0;

    /**
     * @var Collection<int, MunicipalityValue>
     */
    #[ORM\OneToMany(mappedBy: 'indicator', targetEntity: MunicipalityValue::class)]
    private Collection $municipalityValues;

    /**
     * @var Collection<int, ComarcaValue>
     */
    #[ORM\OneToMany(mappedBy: 'indicator', targetEntity: ComarcaValue::class)]
    private Collection $comarcaValues;

    /**
     * @var Collection<int, ProvinceValue>
     */
    #[ORM\OneToMany(mappedBy: 'indicator', targetEntity: ProvinceValue::class, orphanRemoval: true)]
    private Collection $provinceValues;

    /**
     * @var Collection<int, AggregationValue>
     */
    #[ORM\OneToMany(mappedBy: 'indicator', targetEntity: AggregationValue::class)]
    private Collection $aggregationValues;

    // Virtual fields for additional data
    #[Groups(['target', 'indicator'])]
    private ?int $municipalityCount = null;

    #[Groups(['target', 'indicator'])]
    private ?int $yearCount = null;

    #[Groups(['target', 'indicator'])]
    private ?int $lastYearAvailable = null;

    #[Groups(['target', 'indicator'])]
    private ?\DateTimeInterface $mostRecentDate = null;

    public function __construct()
    {
        $this->municipalityValues = new ArrayCollection();
        $this->comarcaValues = new ArrayCollection();
        $this->provinceValues = new ArrayCollection();
        $this->aggregationValues = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getIndicatorId(): ?string
    {
        return $this->indicator_id;
    }

    public function setIndicatorId(?string $indicator_id): static
    {
        $this->indicator_id = $indicator_id;

        return $this;
    }

    public function getTarget(): ?Target
    {
        return $this->target;
    }

    public function setTarget(?Target $target): static
    {
        $this->target = $target;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function isSign(): ?bool
    {
        return $this->sign;
    }

    public function setSign(bool $sign): static
    {
        $this->sign = $sign;

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

    public function getScale(): ?string
    {
        return $this->scale;
    }

    public function setScale(?int $scale): static
    {
        $this->scale = $scale;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function setSource(?string $source): static
    {
        $this->source = $source;

        return $this;
    }

    public function getApiUrlMunicipalities(): ?string
    {
        return $this->api_url_municipalities;
    }

    public function setApiUrlMunicipalities(?string $api_url_municipalities): static
    {
        $this->api_url_municipalities = $api_url_municipalities;

        return $this;
    }

    /**
     * @return Collection<int, MunicipalityValue>
     */
    public function getMunicipalityValues(): Collection
    {
        return $this->municipalityValues;
    }

    public function addMunicipalityValue(MunicipalityValue $municipalityValue): static
    {
        if (!$this->municipalityValues->contains($municipalityValue)) {
            $this->municipalityValues->add($municipalityValue);
            $municipalityValue->setIndicator($this);
        }

        return $this;
    }

    public function removeMunicipalityValue(MunicipalityValue $municipalityValue): static
    {
        if ($this->municipalityValues->removeElement($municipalityValue)) {
            // set the owning side to null (unless already changed)
            if ($municipalityValue->getIndicator() === $this) {
                $municipalityValue->setIndicator(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ComarcaValue>
     */
    public function getComarcaValues(): Collection
    {
        return $this->comarcaValues;
    }

    public function addComarcaValue(ComarcaValue $comarcaValue): static
    {
        if (!$this->comarcaValues->contains($comarcaValue)) {
            $this->comarcaValues->add($comarcaValue);
            $comarcaValue->setIndicator($this);
        }

        return $this;
    }

    public function removeComarcaValue(ComarcaValue $comarcaValue): static
    {
        if ($this->comarcaValues->removeElement($comarcaValue)) {
            // set the owning side to null (unless already changed)
            if ($comarcaValue->getIndicator() === $this) {
                $comarcaValue->setIndicator(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ProvinceValue>
     */
    public function getProvinceValues(): Collection
    {
        return $this->provinceValues;
    }

    public function addProvinceValue(ProvinceValue $provinceValue): static
    {
        if (!$this->provinceValues->contains($provinceValue)) {
            $this->provinceValues->add($provinceValue);
            $provinceValue->setIndicator($this);
        }

        return $this;
    }

    public function removeProvinceValue(ProvinceValue $provinceValue): static
    {
        if ($this->provinceValues->removeElement($provinceValue)) {
            // set the owning side to null (unless already changed)
            if ($provinceValue->getIndicator() === $this) {
                $provinceValue->setIndicator(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, AggregationValue>
     */
    public function getAggregationValues(): Collection
    {
        return $this->aggregationValues;
    }

    public function addAggregationValue(AggregationValue $aggregationValue): static
    {
        if (!$this->aggregationValues->contains($aggregationValue)) {
            $this->aggregationValues->add($aggregationValue);
            $aggregationValue->setIndicator($this);
        }

        return $this;
    }

    public function removeAggregationValue(AggregationValue $aggregationValue): static
    {
        if ($this->aggregationValues->removeElement($aggregationValue)) {
            if ($aggregationValue->getIndicator() === $this) {
                $aggregationValue->setIndicator(null);
            }
        }

        return $this;
    }

    // Getters and setters for properties...

    public function getMunicipalityCount(): ?int
    {
        return $this->municipalityCount;
    }

    public function setMunicipalityCount(?int $municipalityCount): void
    {
        $this->municipalityCount = $municipalityCount;
    }

    public function getYearCount(): ?int
    {
        return $this->yearCount;
    }

    public function setYearCount(?int $yearCount): void
    {
        $this->yearCount = $yearCount;
    }

    public function getLastYearAvailable(): ?int
    {
        return $this->lastYearAvailable;
    }

    public function setLastYearAvailable(?int $year): void
    {
        $this->lastYearAvailable = $year;
    }

    public function getMostRecentDate(): ?\DateTimeInterface
    {
        return $this->mostRecentDate;
    }

    public function setMostRecentDate(?\DateTimeInterface $mostRecentDate): void
    {
        $this->mostRecentDate = $mostRecentDate;
    }

    public function getWeight(): int
    {
        return $this->weight;
    }

    public function setWeight(int $weight): self
    {
        $this->weight = $weight;
        return $this;
    }

    public function getCalculation(): string
    {
        return $this->calculation;
    }

    public function setCalculation(string $calculation): self
    {
        $this->calculation = $calculation;
        return $this;
    }

    public function getDimensionWeight(): int
    {
        return $this->dimension_weight;
    }

    public function setDimensionWeight(int $weight): self
    {
        $this->dimension_weight = $weight;
        return $this;
    }
}
