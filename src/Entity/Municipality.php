<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use App\Repository\MunicipalityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;

#[ORM\Entity(repositoryClass: MunicipalityRepository::class)]
#[ORM\Index(name: 'idx_comarca', columns: ['comarca_id'])]
#[ORM\Index(name: 'idx_code', columns: ['municipality_code'])]
#[ORM\Index(name: 'idx_code_6', columns: ['municipality_code_6'])]
#[ApiResource(
    normalizationContext: ['groups' => ['municipality']],
    paginationEnabled: false,
    operations: [
        new Get(),
        new GetCollection(),
    ]
)]
#[ApiFilter(OrderFilter::class, properties: ['municipality_name', 'municipality_code_6'], arguments: ['orderParameterName' => 'order'])]
#[ApiFilter(SearchFilter::class, properties: ['aggregations.slug' => 'exact'])]
class Municipality
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['municipality', 'municipality_value'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['municipality', 'municipality_value', 'population'])]
    private ?string $municipality_name = null;

    #[ORM\Column(length: 7, unique: true)]
    #[Groups(['municipality', 'municipality_value', 'population'])]
    private ?string $municipality_code = null;

    #[ORM\Column(length: 7, unique: true)]
    #[Groups(['municipality', 'municipality_value'])]
    private ?string $municipality_code_6 = null;

    #[ORM\ManyToOne(inversedBy: 'municipalities')]
    #[Groups('municipality')]
    private ?Comarca $comarca = null;

    /**
     * @var Collection<int, MunicipalityValue>
     */
    #[ORM\OneToMany(mappedBy: 'municipality', targetEntity: MunicipalityValue::class, orphanRemoval: true)]
    // #[Groups('municipality')]
    private Collection $municipalityValues;

    /**
     * @var Collection<int, Population>
     */
    #[ORM\OneToMany(mappedBy: 'municipality', targetEntity: Population::class, orphanRemoval: true)]
    // #[Groups(['municipality', 'municipality_value'])]
    private Collection $populations;

    #[ORM\Column(length: 63)]
    // #[Groups(['municipality'])]
    private ?string $loc = null;

    /**
     * @var Collection<int, Budget>
     */
    #[ORM\OneToMany(mappedBy: 'municipality', targetEntity: Budget::class, orphanRemoval: true)]
    private Collection $budgets;

    #[ORM\Column(nullable: true)]
    #[Groups('municipality')]
    private ?int $population = null;

    #[ORM\Column(nullable: true)]
    #[Groups('municipality')]
    private ?int $population_year = null;

    #[ORM\ManyToOne(inversedBy: 'municipalities')]
    #[Groups('municipality')]
    private ?Ubicacio $ubicacio = null;

    #[ORM\ManyToOne(inversedBy: 'municipalities')]
    #[Groups('municipality')]
    private ?Ruralitat $ruralitat = null;

    #[ORM\Column(name: 'is_industrial', nullable: true)]
    #[Groups('municipality')]
    private ?bool $industrial = null;

    #[ORM\Column(name: 'is_in_amb', nullable: true)]
    #[Groups('municipality')]
    private ?bool $in_amb = null;

    #[ORM\Column(name: 'is_in_rmb', nullable: true)]
    #[Groups('municipality')]
    private ?bool $in_rmb = null;

    #[ORM\ManyToOne]
    #[Groups('municipality')]
    private ?TerritorialRegion $territorial_region = null;

    /**
     * @var Collection<int, Aggregation>
     */
    #[ORM\ManyToMany(targetEntity: Aggregation::class, inversedBy: 'municipalities')]
    #[ORM\JoinTable(name: 'municipality_aggregation')]
    #[Groups('municipality')]
    private Collection $aggregations;

    public function __construct()
    {
        $this->municipalityValues = new ArrayCollection();
        $this->populations = new ArrayCollection();
        $this->budgets = new ArrayCollection();
        $this->aggregations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMunicipalityName(): ?string
    {
        return $this->municipality_name;
    }

    public function setMunicipalityName(string $municipality_name): static
    {
        $this->municipality_name = $municipality_name;

        return $this;
    }

    public function getMunicipalityCode(): ?string
    {
        return $this->municipality_code;
    }

    public function setMunicipalityCode(string $municipality_code): static
    {
        $this->municipality_code = $municipality_code;

        return $this;
    }

    public function getMunicipalityCode6(): ?string
    {
        return $this->municipality_code_6;
    }

    public function setMunicipalityCode6(string $municipality_code_6): static
    {
        $this->municipality_code_6 = $municipality_code_6;

        return $this;
    }

    public function getLoc(): ?string
    {
        return $this->loc;
    }

    public function setLoc(string $loc): static
    {
        $this->loc = $loc;

        return $this;
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
            $municipalityValue->setMunicipality($this);
        }

        return $this;
    }

    public function removeMunicipalityValue(MunicipalityValue $municipalityValue): static
    {
        if ($this->municipalityValues->removeElement($municipalityValue)) {
            // set the owning side to null (unless already changed)
            if ($municipalityValue->getMunicipality() === $this) {
                $municipalityValue->setMunicipality(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Population>
     */
    public function getPopulations(): Collection
    {
        return $this->populations;
    }

    public function addPopulation(Population $population): static
    {
        if (!$this->populations->contains($population)) {
            $this->populations->add($population);
            $population->setMunicipality($this);
        }

        return $this;
    }

    public function removePopulation(Population $population): static
    {
        if ($this->populations->removeElement($population)) {
            // set the owning side to null (unless already changed)
            if ($population->getMunicipality() === $this) {
                $population->setMunicipality(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Budget>
     */
    public function getBudgets(): Collection
    {
        return $this->budgets;
    }

    public function addBudget(Budget $budget): static
    {
        if (!$this->budgets->contains($budget)) {
            $this->budgets->add($budget);
            $budget->setMunicipality($this);
        }

        return $this;
    }

    public function removeBudget(Budget $budget): static
    {
        if ($this->budgets->removeElement($budget)) {
            // set the owning side to null (unless already changed)
            if ($budget->getMunicipality() === $this) {
                $budget->setMunicipality(null);
            }
        }

        return $this;
    }

    public function getPopulation(): ?int
    {
        return $this->population;
    }

    public function setPopulation(?int $population): static
    {
        $this->population = $population;

        return $this;
    }

    public function getPopulationYear(): ?int
    {
        return $this->population_year;
    }

    public function setPopulationYear(?int $population_year): static
    {
        $this->population_year = $population_year;

        return $this;
    }

    public function getUbicacio(): ?Ubicacio
    {
        return $this->ubicacio;
    }

    public function setUbicacio(?Ubicacio $ubicacio): static
    {
        $this->ubicacio = $ubicacio;

        return $this;
    }

    public function getRuralitat(): ?Ruralitat
    {
        return $this->ruralitat;
    }

    public function setRuralitat(?Ruralitat $ruralitat): static
    {
        $this->ruralitat = $ruralitat;

        return $this;
    }

    public function isIndustrial(): ?bool
    {
        return $this->industrial;
    }

    public function setIndustrial(?bool $industrial): static
    {
        $this->industrial = $industrial;

        return $this;
    }

    public function isInAmb(): ?bool
    {
        return $this->in_amb;
    }

    public function setInAmb(?bool $in_amb): static
    {
        $this->in_amb = $in_amb;

        return $this;
    }

    public function isInRmb(): ?bool
    {
        return $this->in_rmb;
    }

    public function setInRmb(?bool $in_rmb): static
    {
        $this->in_rmb = $in_rmb;

        return $this;
    }

    public function getTerritorialRegion(): ?TerritorialRegion
    {
        return $this->territorial_region;
    }

    public function setTerritorialRegion(?TerritorialRegion $territorial_region): static
    {
        $this->territorial_region = $territorial_region;

        return $this;
    }

    /**
     * @return Collection<int, Aggregation>
     */
    public function getAggregations(): Collection
    {
        return $this->aggregations;
    }

    public function addAggregation(Aggregation $aggregation): static
    {
        if (!$this->aggregations->contains($aggregation)) {
            $this->aggregations->add($aggregation);
        }

        return $this;
    }

    public function removeAggregation(Aggregation $aggregation): static
    {
        $this->aggregations->removeElement($aggregation);

        return $this;
    }
}
