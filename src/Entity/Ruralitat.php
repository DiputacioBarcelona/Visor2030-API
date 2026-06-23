<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\RuralitatRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use ApiPlatform\Metadata\GetCollection;

#[ORM\Entity(repositoryClass: RuralitatRepository::class)]
#[ApiResource(
    operations: [
        // new Get(),
        new GetCollection(),
    ]
)]
class Ruralitat
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['municipality'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['municipality'])]
    private ?string $name = null;

    /**
     * @var Collection<int, Municipality>
     */
    #[ORM\OneToMany(mappedBy: 'ruralitat', targetEntity: Municipality::class)]
    private Collection $municipalities;

    public function __construct()
    {
        $this->municipalities = new ArrayCollection();
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
            $municipality->setRuralitat($this);
        }

        return $this;
    }

    public function removeMunicipality(Municipality $municipality): static
    {
        if ($this->municipalities->removeElement($municipality)) {
            // set the owning side to null (unless already changed)
            if ($municipality->getRuralitat() === $this) {
                $municipality->setRuralitat(null);
            }
        }

        return $this;
    }
}
