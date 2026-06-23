<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\AggregationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: AggregationRepository::class)]
#[ApiResource(
    normalizationContext: ['groups' => ['aggregation']],
    paginationEnabled: false,
    operations: [new GetCollection()]
)]
#[ApiFilter(SearchFilter::class, properties: ['group' => 'exact', 'slug' => 'exact'])]
class Aggregation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['aggregation', 'municipality'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['aggregation', 'municipality', 'aggregation_value'])]
    private ?string $name = null;

    #[ORM\Column(length: 100, unique: true)]
    #[Groups(['aggregation', 'municipality', 'aggregation_value'])]
    private ?string $slug = null;

    #[ORM\Column(name: 'agg_group', length: 50)]
    #[Groups(['aggregation', 'municipality', 'aggregation_value'])]
    private ?string $group = null;

    /**
     * @var Collection<int, Municipality>
     */
    #[ORM\ManyToMany(targetEntity: Municipality::class, mappedBy: 'aggregations')]
    private Collection $municipalities;

    /**
     * @var Collection<int, AggregationValue>
     */
    #[ORM\OneToMany(mappedBy: 'aggregation', targetEntity: AggregationValue::class, orphanRemoval: true)]
    private Collection $aggregationValues;

    public function __construct()
    {
        $this->municipalities = new ArrayCollection();
        $this->aggregationValues = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    public function getGroup(): ?string
    {
        return $this->group;
    }

    public function setGroup(string $group): static
    {
        $this->group = $group;

        return $this;
    }

    /**
     * @return Collection<int, Municipality>
     */
    public function getMunicipalities(): Collection
    {
        return $this->municipalities;
    }

    public function addMunicipality(Municipality $municipality): static
    {
        if (!$this->municipalities->contains($municipality)) {
            $this->municipalities->add($municipality);
            $municipality->addAggregation($this);
        }

        return $this;
    }

    public function removeMunicipality(Municipality $municipality): static
    {
        if ($this->municipalities->removeElement($municipality)) {
            $municipality->removeAggregation($this);
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
            $aggregationValue->setAggregation($this);
        }

        return $this;
    }

    public function removeAggregationValue(AggregationValue $aggregationValue): static
    {
        if ($this->aggregationValues->removeElement($aggregationValue)) {
            if ($aggregationValue->getAggregation() === $this) {
                $aggregationValue->setAggregation(null);
            }
        }

        return $this;
    }
}
