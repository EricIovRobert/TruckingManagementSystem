<?php

namespace App\Entity;

use App\Repository\ComenziRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ComenziRepository::class)]
class Comenzi
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'comenzis')]
    #[ORM\JoinColumn(nullable: false)]
    private ?ParcAuto $parcAuto = null;

    #[ORM\Column(length: 100)]
    private ?string $sofer = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $dataStart = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $dataStop = null;

    #[ORM\Column]
    private ?float $numarKm = null;

    #[ORM\Column(nullable: true)]
    private ?float $profit = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getParcAuto(): ?ParcAuto
    {
        return $this->parcAuto;
    }

    public function setParcAuto(?ParcAuto $parcAuto): static
    {
        $this->parcAuto = $parcAuto;

        return $this;
    }

    public function getSofer(): ?string
    {
        return $this->sofer;
    }

    public function setSofer(string $sofer): static
    {
        $this->sofer = $sofer;

        return $this;
    }

    public function getDataStart(): ?\DateTimeInterface
    {
        return $this->dataStart;
    }

    public function setDataStart(\DateTimeInterface $dataStart): static
    {
        $this->dataStart = $dataStart;

        return $this;
    }

    public function getDataStop(): ?\DateTimeInterface
    {
        return $this->dataStop;
    }

    public function setDataStop(\DateTimeInterface $dataStop): static
    {
        $this->dataStop = $dataStop;

        return $this;
    }

    public function getNumarKm(): ?float
    {
        return $this->numarKm;
    }

    public function setNumarKm(float $numarKm): static
    {
        $this->numarKm = $numarKm;

        return $this;
    }

    public function getProfit(): ?float
    {
        return $this->profit;
    }

    public function setProfit(?float $profit): static
    {
        $this->profit = $profit;

        return $this;
    }
}
