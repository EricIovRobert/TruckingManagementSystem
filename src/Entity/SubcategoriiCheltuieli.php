<?php

namespace App\Entity;

use App\Repository\SubcategoriiCheltuieliRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SubcategoriiCheltuieliRepository::class)]
class SubcategoriiCheltuieli
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'subcategoriiCheltuielis')]
    #[ORM\JoinColumn(nullable: false)]
    private ?CategoriiCheltuieli $categorie = null;

    #[ORM\Column(length: 100)]
    private ?string $nume = null;

    #[ORM\Column(nullable: true)]
    private ?float $pret_standard = null;

    #[ORM\Column(nullable: true)]
    private ?float $pret_per_l = null;

    /**
     * @var Collection<int, Cheltuieli>
     */
    #[ORM\OneToMany(targetEntity: Cheltuieli::class, mappedBy: 'subcategorie')]
    private Collection $cheltuielis;

    public function __construct()
    {
        $this->cheltuielis = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCategorie(): ?CategoriiCheltuieli
    {
        return $this->categorie;
    }

    public function setCategorie(?CategoriiCheltuieli $categorie): static
    {
        $this->categorie = $categorie;

        return $this;
    }

    public function getNume(): ?string
    {
        return $this->nume;
    }

    public function setNume(string $nume): static
    {
        $this->nume = $nume;

        return $this;
    }

    public function getPretStandard(): ?float
    {
        return $this->pret_standard;
    }

    public function setPretStandard(?float $pret_standard): static
    {
        $this->pret_standard = $pret_standard;

        return $this;
    }

    public function getPretPerL(): ?float
    {
        return $this->pret_per_l;
    }

    public function setPretPerL(?float $pret_per_l): static
    {
        $this->pret_per_l = $pret_per_l;

        return $this;
    }

    /**
     * @return Collection<int, Cheltuieli>
     */
    public function getCheltuielis(): Collection
    {
        return $this->cheltuielis;
    }

    public function addCheltuieli(Cheltuieli $cheltuieli): static
    {
        if (!$this->cheltuielis->contains($cheltuieli)) {
            $this->cheltuielis->add($cheltuieli);
            $cheltuieli->setSubcategorie($this);
        }

        return $this;
    }

    public function removeCheltuieli(Cheltuieli $cheltuieli): static
    {
        if ($this->cheltuielis->removeElement($cheltuieli)) {
            // set the owning side to null (unless already changed)
            if ($cheltuieli->getSubcategorie() === $this) {
                $cheltuieli->setSubcategorie(null);
            }
        }

        return $this;
    }
}
