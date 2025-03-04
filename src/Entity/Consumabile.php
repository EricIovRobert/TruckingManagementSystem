<?php

namespace App\Entity;

use App\Repository\ConsumabileRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ConsumabileRepository::class)]
class Consumabile
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'consumabiles')]
    #[ORM\JoinColumn(nullable: false)]
    private ?CategoriiCheltuieli $categorie = null;

    #[ORM\Column(length: 100)]
    private ?string $nume = null;

    #[ORM\Column]
    private ?float $pret_maxim = null;

    #[ORM\Column(nullable: true)]
    private ?float $km_utilizare_max = null;

    /**
     * @var Collection<int, Cheltuieli>
     */
    #[ORM\OneToMany(targetEntity: Cheltuieli::class, mappedBy: 'consumabil')]
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

    public function getPretMaxim(): ?float
    {
        return $this->pret_maxim;
    }

    public function setPretMaxim(float $pret_maxim): static
    {
        $this->pret_maxim = $pret_maxim;

        return $this;
    }

    public function getKmUtilizareMax(): ?float
    {
        return $this->km_utilizare_max;
    }

    public function setKmUtilizareMax(?float $km_utilizare_max): static
    {
        $this->km_utilizare_max = $km_utilizare_max;

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
            $cheltuieli->setConsumabil($this);
        }

        return $this;
    }

    public function removeCheltuieli(Cheltuieli $cheltuieli): static
    {
        if ($this->cheltuielis->removeElement($cheltuieli)) {
            // set the owning side to null (unless already changed)
            if ($cheltuieli->getConsumabil() === $this) {
                $cheltuieli->setConsumabil(null);
            }
        }

        return $this;
    }
}
