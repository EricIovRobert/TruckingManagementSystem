<?php

namespace App\Entity;

use App\Repository\ConsumabileRepository;
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
}
