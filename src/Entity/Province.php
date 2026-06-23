<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\ProvinceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;

#[ORM\Entity(repositoryClass: ProvinceRepository::class)]
#[ApiResource(
    operations: [
        // new Get(),
        // new GetCollection(),
    ]
)]
class Province
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 7, unique: true)]
    private ?string $province_code = null;

    #[ORM\Column(length: 255)]
    private ?string $province_name = null;

    /**
     * @var Collection<int, Comarca>
     */
    #[ORM\OneToMany(mappedBy: 'province', targetEntity: Comarca::class)]
    private Collection $comarcas;

    /**
     * @var Collection<int, ProvinceValue>
     */
    #[ORM\OneToMany(mappedBy: 'province', targetEntity: ProvinceValue::class, orphanRemoval: true)]
    private Collection $provinceValues;

    public function __construct()
    {
        $this->comarcas = new ArrayCollection();
        $this->provinceValues = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProvinceCode(): ?string
    {
        return $this->province_code;
    }

    public function setProvinceCode(string $province_code): static
    {
        $this->province_code = $province_code;

        return $this;
    }

    public function getProvinceName(): ?string
    {
        return $this->province_name;
    }

    public function setProvinceName(string $province_name): static
    {
        $this->province_name = $province_name;

        return $this;
    }

    /**
     * @return Collection<int, Comarca>
     */
    public function getComarcas(): Collection
    {
        return $this->comarcas;
    }

    public function addComarca(Comarca $comarca): static
    {
        if (!$this->comarcas->contains($comarca)) {
            $this->comarcas->add($comarca);
            $comarca->setProvince($this);
        }

        return $this;
    }

    public function removeComarca(Comarca $comarca): static
    {
        if ($this->comarcas->removeElement($comarca)) {
            // set the owning side to null (unless already changed)
            if ($comarca->getProvince() === $this) {
                $comarca->setProvince(null);
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
            $provinceValue->setProvince($this);
        }

        return $this;
    }

    public function removeProvinceValue(ProvinceValue $provinceValue): static
    {
        if ($this->provinceValues->removeElement($provinceValue)) {
            // set the owning side to null (unless already changed)
            if ($provinceValue->getProvince() === $this) {
                $provinceValue->setProvince(null);
            }
        }

        return $this;
    }

    /**
     * Custom method to "jump" and get all municipalities in the province
     *
     * @return Municipality[]
     */
    public function getMunicipalities(): array
    {
        $municipalities = [];

        foreach ($this->comarcas as $comarca) {
            foreach ($comarca->getMunicipalities() as $municipality) {
                $municipalities[] = $municipality;
            }
        }

        return $municipalities;
    }
}
