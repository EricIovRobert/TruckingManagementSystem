<?php

namespace App\Entity;

use App\Repository\TururiRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TururiRepository::class)]
class Tururi
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'tururis')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Comenzi $comanda = null;

    #[ORM\Column(length: 100)]
    private ?string $firma = null;

    #[ORM\Column(length: 255)]
    private ?string $rutaIncarcare = null;

    #[ORM\Column(length: 255)]
    private ?string $rutaDescarcare = null;

    #[ORM\Column]
    private ?float $kg = null;

    #[ORM\Column]
    private ?float $pret = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $liber = null;

    #[ORM\Column]
    private ?bool $facturat = false;

    

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

    public function getFirma(): ?string
    {
        return $this->firma;
    }

    public function setFirma(string $firma): static
    {
        $this->firma = $firma;

        return $this;
    }

    public function getRutaIncarcare(): ?string
    {
        return $this->rutaIncarcare;
    }

    public function setRutaIncarcare(string $rutaIncarcare): static
    {
        $this->rutaIncarcare = $rutaIncarcare;

        return $this;
    }

    public function getRutaDescarcare(): ?string
    {
        return $this->rutaDescarcare;
    }

    public function setRutaDescarcare(string $rutaDescarcare): static
    {
        $this->rutaDescarcare = $rutaDescarcare;

        return $this;
    }

    public function getKg(): ?float
    {
        return $this->kg;
    }

    public function setKg(float $kg): static
    {
        $this->kg = $kg;

        return $this;
    }

    public function getPret(): ?float
    {
        return $this->pret;
    }

    public function setPret(float $pret): static
    {
        $this->pret = $pret;

        return $this;
    }

    public function getLiber(): ?string
    {
        return $this->liber;
    }

    public function setLiber(?string $liber): static
    {
        $this->liber = $liber;

        return $this;
    }

    public function isFacturat(): ?bool
    {
        return $this->facturat;
    }

    public function setFacturat(bool $facturat): static
    {
        $this->facturat = $facturat;

        return $this;
    }
}
