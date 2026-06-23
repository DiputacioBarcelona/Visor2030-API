<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\ComarcaRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;

#[ORM\Entity(repositoryClass: ComarcaRepository::class)]
#[ORM\Index(name: 'idx_code', columns: ['comarca_code'])]
#[ApiResource(
    operations: [
        // new Get(),
        new GetCollection(),
    ]
)]
class Comarca
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['municipality', 'comarca_value'])]
    private ?string $comarca_name = null;

    #[ORM\Column(length: 7, unique: true)]
    #[Groups(['municipality', 'comarca_value'])]
    private ?string $comarca_code = null;

    #[ORM\ManyToOne(inversedBy: 'comarcas')]
    private ?Province $province = null;

    /**
     * @var Collection<int, Municipality>
     */
    #[ORM\OneToMany(mappedBy: 'comarca', targetEntity: Municipality::class)]
    private Collection $municipalities;

    /**
     * @var Collection<int, ComarcaValue>
     */
    #[ORM\OneToMany(mappedBy: 'comarca', targetEntity: ComarcaValue::class, orphanRemoval: true)]
    private Collection $comarcaValues;

    public function __construct()
    {
        $this->municipalities = new ArrayCollection();
        $this->comarcaValues = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getComarcaName(): ?string
    {
        return $this->comarca_name;
    }

    public function setComarcaName(string $comarca_name): static
    {
        $this->comarca_name = $comarca_name;

        return $this;
    }

    public function getComarcaCode(): ?string
    {
        return $this->comarca_code;
    }

    public function setComarcaCode(string $comarca_code): static
    {
        $this->comarca_code = $comarca_code;

        return $this;
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
            $municipality->setComarca($this);
        }

        return $this;
    }

    public function removeMunicipality(Municipality $municipality): static
    {
        if ($this->municipalities->removeElement($municipality)) {
            // set the owning side to null (unless already changed)
            if ($municipality->getComarca() === $this) {
                $municipality->setComarca(null);
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
            $comarcaValue->setComarca($this);
        }

        return $this;
    }

    public function removeComarcaValue(ComarcaValue $comarcaValue): static
    {
        if ($this->comarcaValues->removeElement($comarcaValue)) {
            // set the owning side to null (unless already changed)
            if ($comarcaValue->getComarca() === $this) {
                $comarcaValue->setComarca(null);
            }
        }

        return $this;
    }
}
