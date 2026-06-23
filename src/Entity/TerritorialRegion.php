<?php

namespace App\Entity;

use App\Repository\TerritorialRegionRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: TerritorialRegionRepository::class)]
class TerritorialRegion
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups('municipality')]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups('municipality')]
    private ?string $name = null;

    public function getId(): ?int
    {
        return $this->id;
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
}
