<?php

namespace App\Entity;

use App\Repository\CheltuieliRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CheltuieliRepository::class)]
class Cheltuieli
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'cheltuielis')]
    private ?Comenzi $comanda = null;

    #[ORM\ManyToOne(inversedBy: 'cheltuielis')]
    private ?ComenziComunitare $comunitar = null;

    #[ORM\ManyToOne(inversedBy: 'cheltuielis')]
    #[ORM\JoinColumn(nullable: false)]
    private ?CategoriiCheltuieli $categorie = null;

    #[ORM\ManyToOne(inversedBy: 'cheltuielis')]
    private ?SubcategoriiCheltuieli $subcategorie = null;

    #[ORM\ManyToOne(inversedBy: 'cheltuielis')]
    private ?Consumabile $consumabil = null;

    #[ORM\Column]
    private ?float $suma = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $data_cheltuiala = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $descriere = null;

    #[ORM\Column(nullable: true)]
    private ?float $litri_motorina = null;

    #[ORM\Column(nullable: true)]
    private ?float $tva = null;

    #[ORM\Column(nullable: true)]
    private ?float $comision_tva = null;

    #[ORM\Column(nullable: true)]
    private ?float $comision_taxa_drum = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getComanda(): ?Comenzi
    {
        return $this->comanda;
    }

    public function setComanda(?Comenzi $comanda): static
    {
        $this->comanda = $comanda;

        return $this;
    }

    public function getComunitar(): ?ComenziComunitare
    {
        return $this->comunitar;
    }

    public function setComunitar(?ComenziComunitare $comunitar): static
    {
        $this->comunitar = $comunitar;

        return $this;
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

    public function getSubcategorie(): ?SubcategoriiCheltuieli
    {
        return $this->subcategorie;
    }

    public function setSubcategorie(?SubcategoriiCheltuieli $subcategorie): static
    {
        $this->subcategorie = $subcategorie;

        return $this;
    }

    public function getConsumabil(): ?Consumabile
    {
        return $this->consumabil;
    }

    public function setConsumabil(?Consumabile $consumabil): static
    {
        $this->consumabil = $consumabil;

        return $this;
    }

    public function getSuma(): ?float
    {
        return $this->suma;
    }

    public function setSuma(float $suma): static
    {
        $this->suma = $suma;

        return $this;
    }

    public function getDataCheltuiala(): ?\DateTimeInterface
    {
        return $this->data_cheltuiala;
    }

    public function setDataCheltuiala(\DateTimeInterface $data_cheltuiala): static
    {
        $this->data_cheltuiala = $data_cheltuiala;

        return $this;
    }

    public function getDescriere(): ?string
    {
        return $this->descriere;
    }

    public function setDescriere(?string $descriere): static
    {
        $this->descriere = $descriere;

        return $this;
    }

    public function getLitriMotorina(): ?float
    {
        return $this->litri_motorina;
    }

    public function setLitriMotorina(?float $litri_motorina): static
    {
        $this->litri_motorina = $litri_motorina;

        return $this;
    }

    public function getTva(): ?float
    {
        return $this->tva;
    }

    public function setTva(?float $tva): static
    {
        $this->tva = $tva;

        return $this;
    }

    public function getComisionTva(): ?float
    {
        return $this->comision_tva;
    }

    public function setComisionTva(?float $comision_tva): static
    {
        $this->comision_tva = $comision_tva;

        return $this;
    }

    public function getComisionTaxaDrum(): ?float
    {
        return $this->comision_taxa_drum;
    }

    public function setComisionTaxaDrum(?float $comision_taxa_drum): static
    {
        $this->comision_taxa_drum = $comision_taxa_drum;

        return $this;
    }
}
