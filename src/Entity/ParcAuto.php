<?php

namespace App\Entity;

use App\Repository\ParcAutoRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ParcAutoRepository::class)]
class ParcAuto
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20)]
    private ?string $nrAuto = null;

    #[ORM\Column(length: 10)]
    private ?string $an = null;


    #[ORM\Column(length: 100)]
    private ?string $categorieMasina = null;

    /**
     * @var Collection<int, Comenzi>
     */
    #[ORM\OneToMany(targetEntity: Comenzi::class, mappedBy: 'parcAuto')]
    private Collection $comenzis;

    public function __construct()
    {
        $this->comenzis = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNrAuto(): ?string
    {
        return $this->nrAuto;
    }

    public function setNrAuto(string $nrAuto): static
    {
        $this->nrAuto = $nrAuto;

        return $this;
    }

    public function getAn(): ?string
    {
        return $this->an;
    }

    public function setAn(string $an): static
    {
        $this->an = $an;

        return $this;
    }

    public function getCategorieMasina(): ?string
    {
        return $this->categorieMasina;
    }

    public function setCategorieMasina(string $categorieMasina): static
    {
        $this->categorieMasina = $categorieMasina;

        return $this;
    }

    /**
     * @return Collection<int, Comenzi>
     */
    public function getComenzis(): Collection
    {
        return $this->comenzis;
    }

    public function addComenzi(Comenzi $comenzi): static
    {
        if (!$this->comenzis->contains($comenzi)) {
            $this->comenzis->add($comenzi);
            $comenzi->setParcAuto($this);
        }

        return $this;
    }

    public function removeComenzi(Comenzi $comenzi): static
    {
        if ($this->comenzis->removeElement($comenzi)) {
            // set the owning side to null (unless already changed)
            if ($comenzi->getParcAuto() === $this) {
                $comenzi->setParcAuto(null);
            }
        }

        return $this;
    }
}
