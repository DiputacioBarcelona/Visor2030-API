<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use App\Repository\TargetRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;

#[ORM\Entity(repositoryClass: TargetRepository::class)]
#[ApiResource(
    normalizationContext: ['groups' => ['target']],
    paginationEnabled: false,
    order: ['sdg' => 'ASC', 'target_id' => 'ASC'],
    operations: [
        // new Get(),
        new GetCollection(),
    ]
)]
#[ApiFilter(SearchFilter::class, properties: [
    'sdg' => 'exact',
])]
class Target
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['target', 'indicator'])]
    private ?int $id = null;

    #[ORM\Column(type: Types::SMALLINT)]
    #[Groups(['municipality', 'target', 'indicator'])]
    private ?int $sdg = null;

    #[ORM\Column(length: 7, nullable: true, unique: true)]
    #[Groups(['municipality', 'municipality_value', 'target', 'indicator'])]
    private ?string $target_id = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['municipality', 'target'])]
    private ?string $target_name = null;

    /**
     * @var Collection<int, Indicator>
     */
    #[ORM\OneToMany(mappedBy: 'target', targetEntity: Indicator::class, orphanRemoval: true)]
    #[Groups(['target'])]
    #[ORM\OrderBy(['indicator_id' => 'ASC'])]
    private Collection $indicators;

    public function __construct()
    {
        $this->indicators = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSdg(): ?int
    {
        return $this->sdg;
    }

    public function setSdg(int $sdg): static
    {
        $this->sdg = $sdg;

        return $this;
    }

    public function getTargetId(): ?string
    {
        return $this->target_id;
    }

    public function setTargetId(?string $target_id): static
    {
        $this->target_id = $target_id;

        return $this;
    }

    public function getTargetName(): ?string
    {
        return $this->target_name;
    }

    public function setTargetName(?string $target_name): static
    {
        $this->target_name = $target_name;

        return $this;
    }

    /**
     * @return Collection<int, Indicator>
     */
    public function getIndicators(): Collection
    {
        return $this->indicators;
    }

    public function addIndicator(Indicator $indicator): static
    {
        if (!$this->indicators->contains($indicator)) {
            $this->indicators->add($indicator);
            $indicator->setTarget($this);
        }

        return $this;
    }

    public function removeIndicator(Indicator $indicator): static
    {
        if ($this->indicators->removeElement($indicator)) {
            // set the owning side to null (unless already changed)
            if ($indicator->getTarget() === $this) {
                $indicator->setTarget(null);
            }
        }

        return $this;
    }
}
