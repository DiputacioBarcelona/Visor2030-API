<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\BudgetRepository;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use Symfony\Component\Serializer\Annotation\Groups;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;

#[ORM\Entity(repositoryClass: BudgetRepository::class)]
#[ApiResource(
    normalizationContext: ['groups' => ['budget']],
    // change default pagination to 300
    paginationItemsPerPage: 2000,
    operations: [
        // new Get(),
        new GetCollection(),
    ]
)]
#[ApiFilter(SearchFilter::class, properties: [
    'municipality.id' => 'exact',
    'municipality.municipality_code_6' => 'exact',
    'municipality.municipality_code' => 'exact',
    'municipality.comarca.comarca_code' => 'exact',
    'program' => 'exact',
    'year' => 'exact'])]
#[ApiFilter(OrderFilter::class, properties: ['program'], arguments: ['orderParameterName' => 'order'])]
#[ORM\Index(name: 'idx_year', columns: ['year'])]
#[ORM\Index(name: 'idx_program', columns: ['program'])]
class Budget
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    #[Groups(['budget'])]
    private ?int $year = null;

    #[ORM\Column]
    #[Groups(['budget'])]
    private ?float $value = null;

    #[ORM\Column(length: 6)]
    #[Groups(['budget'])]
    private ?string $program = null;

    #[ORM\ManyToOne(inversedBy: 'budgets')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['budget'])]
    private ?Municipality $municipality = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getYear(): ?int
    {
        return $this->year;
    }

    public function setYear(int $year): static
    {
        $this->year = $year;

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

    public function getProgram(): ?string
    {
        return $this->program;
    }

    public function setProgram(string $program): static
    {
        $this->program = $program;

        return $this;
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
}
