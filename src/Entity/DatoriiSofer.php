<?php

namespace App\Entity;

use App\Repository\DatoriiSoferRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DatoriiSoferRepository::class)]
class DatoriiSofer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nume_sofer = null;

    #[ORM\Column(length: 255)]
    private ?string $denumire = null;

    #[ORM\Column]
    private ?float $suma = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $data = null;

    #[ORM\Column(nullable: true)]
    private ?float $achitata = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $observatii = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumeSofer(): ?string
    {
        return $this->nume_sofer;
    }

    public function setNumeSofer(string $nume_sofer): static
    {
        $this->nume_sofer = $nume_sofer;

        return $this;
    }

    public function getDenumire(): ?string
    {
        return $this->denumire;
    }

    public function setDenumire(string $denumire): static
    {
        $this->denumire = $denumire;

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

    public function getData(): ?\DateTimeInterface
    {
        return $this->data;
    }

    public function setData(\DateTimeInterface $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function getAchitata(): ?float
    {
        return $this->achitata;
    }

    public function setAchitata(?float $achitata): static
    {
        $this->achitata = $achitata;

        return $this;
    }

    public function getObservatii(): ?string
    {
        return $this->observatii;
    }

    public function setObservatii(?string $observatii): static
    {
        $this->observatii = $observatii;

        return $this;
    }
}
