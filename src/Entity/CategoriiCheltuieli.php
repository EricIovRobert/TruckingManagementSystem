<?php

namespace App\Entity;

use App\Repository\CategoriiCheltuieliRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CategoriiCheltuieliRepository::class)]
class CategoriiCheltuieli
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $nume = null;

    /**
     * @var Collection<int, SubcategoriiCheltuieli>
     */
    #[ORM\OneToMany(targetEntity: SubcategoriiCheltuieli::class, mappedBy: 'categorie', cascade: ['remove'])]
    private Collection $subcategoriiCheltuielis;

    /**
     * @var Collection<int, Consumabile>
     */
    #[ORM\OneToMany(targetEntity: Consumabile::class, mappedBy: 'categorie', cascade: ['remove'])]
    private Collection $consumabiles;

    /**
     * @var Collection<int, Cheltuieli>
     */
    #[ORM\OneToMany(targetEntity: Cheltuieli::class, mappedBy: 'categorie')]
    private Collection $cheltuielis;

    public function __construct()
    {
        $this->subcategoriiCheltuielis = new ArrayCollection();
        $this->consumabiles = new ArrayCollection();
        $this->cheltuielis = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    /**
     * @return Collection<int, SubcategoriiCheltuieli>
     */
    public function getSubcategoriiCheltuielis(): Collection
    {
        return $this->subcategoriiCheltuielis;
    }

    public function addSubcategoriiCheltuieli(SubcategoriiCheltuieli $subcategoriiCheltuieli): static
    {
        if (!$this->subcategoriiCheltuielis->contains($subcategoriiCheltuieli)) {
            $this->subcategoriiCheltuielis->add($subcategoriiCheltuieli);
            $subcategoriiCheltuieli->setCategorie($this);
        }
        return $this;
    }

    public function removeSubcategoriiCheltuieli(SubcategoriiCheltuieli $subcategoriiCheltuieli): static
    {
        if ($this->subcategoriiCheltuielis->removeElement($subcategoriiCheltuieli)) {
            if ($subcategoriiCheltuieli->getCategorie() === $this) {
                $subcategoriiCheltuieli->setCategorie(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, Consumabile>
     */
    public function getConsumabiles(): Collection
    {
        return $this->consumabiles;
    }

    public function addConsumabile(Consumabile $consumabile): static
    {
        if (!$this->consumabiles->contains($consumabile)) {
            $this->consumabiles->add($consumabile);
            $consumabile->setCategorie($this);
        }
        return $this;
    }

    public function removeConsumabile(Consumabile $consumabile): static
    {
        if ($this->consumabiles->removeElement($consumabile)) {
            if ($consumabile->getCategorie() === $this) {
                $consumabile->setCategorie(null);
            }
        }
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
            $cheltuieli->setCategorie($this);
        }

        return $this;
    }

    public function removeCheltuieli(Cheltuieli $cheltuieli): static
    {
        if ($this->cheltuielis->removeElement($cheltuieli)) {
            // set the owning side to null (unless already changed)
            if ($cheltuieli->getCategorie() === $this) {
                $cheltuieli->setCategorie(null);
            }
        }

        return $this;
    }
}