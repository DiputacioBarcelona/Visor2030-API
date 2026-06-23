<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use App\Repository\PopulationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;

#[ORM\Entity(repositoryClass: PopulationRepository::class)]
#[ApiResource(
    normalizationContext: ['groups' => ['population']],
    paginationItemsPerPage: 100,
    paginationMaximumItemsPerPage: 1000,
    paginationClientItemsPerPage: true,
    operations: [
        new Get(),
        new GetCollection(),
    ]
)]
#[ApiFilter(SearchFilter::class, properties: [
    'year' => 'exact',
    'municipality.municipality_code' => 'exact',
])]
class Population
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'populations')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['population'])]
    private ?Municipality $municipality = null;

    #[ORM\Column]
    #[Groups(['municipality_value', 'population'])]
    private ?int $population_count = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    #[Groups(['municipality_value', 'population'])]
    private ?int $year = null;

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

    public function getPopulationCount(): ?int
    {
        return $this->population_count;
    }

    public function setPopulationCount(int $population_count): static
    {
        $this->population_count = $population_count;

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
}
