<?php

namespace App\Entity;

use App\Repository\ComenziComunitareRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ComenziComunitareRepository::class)]
class ComenziComunitare
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'comenziComunitares')]
    #[ORM\JoinColumn(nullable: false)]
    private ?ParcAuto $nr_auto = null;

    #[ORM\Column(length: 20)]
    private ?string $nr_auto_snapshot = null;

    #[ORM\Column(length: 100)]
    private ?string $sofer = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $data_start = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $data_stop = null;

    #[ORM\Column(nullable: true)]
    private ?float $nr_km = null;

    #[ORM\Column(nullable: true)]
    private ?float $profit = null;

    #[ORM\Column]
    private ?float $kg = null;

    #[ORM\Column]
    private ?float $pret = null;

    #[ORM\Column(length: 100)]
    private ?string $firma = null;

    /**
     * @var Collection<int, Cheltuieli>
     */
    #[ORM\OneToMany(targetEntity: Cheltuieli::class, mappedBy: 'comunitar', cascade: ['remove'])]
    private Collection $cheltuielis;

    #[ORM\Column(length: 100)]
    private ?string $remorca = null;

    #[ORM\Column]
    private ?bool $calculata = false;

    public function __construct()
    {
        $this->cheltuielis = new ArrayCollection();
        $this->calculata = false; 
    }


public function calculateAndSetProfit(): void
{
    $totalCheltuieli = 0;
    foreach ($this->cheltuielis as $cheltuiala) {
        $sumaBruta = $cheltuiala->getSuma();
        $tvaProcent = $cheltuiala->getTva() ?? 0;
        $comisionTvaProcent = $cheltuiala->getComisionTva() ?? 0;
        $comisionTaxaDrumProcent = $cheltuiala->getComisionTaxaDrum() ?? 0;
        
        // Comision taxa de drum = procent din suma bruta
        $comisionTaxaDrum = $sumaBruta * ($comisionTaxaDrumProcent / 100);
        
        $tvaValue = ($sumaBruta * $tvaProcent / (100 + $tvaProcent));
        $comisionTva = ($tvaValue * $comisionTvaProcent / 100);
        $cheltuialaNeta = $sumaBruta - $tvaValue + $comisionTva + $comisionTaxaDrum;
        
        $totalCheltuieli += $cheltuialaNeta;
    }
    
    $profit = $this->pret - $totalCheltuieli;
    $this->setProfit($profit);
}

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNrAuto(): ?ParcAuto
    {
        return $this->nr_auto;
    }

    public function setNrAuto(?ParcAuto $nr_auto): static
    {
        $this->nr_auto = $nr_auto;

        return $this;
    }

    public function getNrAutoSnapshot(): ?string
    {
        return $this->nr_auto_snapshot;
    }

    public function setNrAutoSnapshot(string $nr_auto_snapshot): static
    {
        $this->nr_auto_snapshot = $nr_auto_snapshot;

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
        return $this->data_start;
    }

    public function setDataStart(\DateTimeInterface $data_start): static
    {
        $this->data_start = $data_start;

        return $this;
    }

    public function getDataStop(): ?\DateTimeInterface
    {
        return $this->data_stop;
    }

    public function setDataStop(?\DateTimeInterface $data_stop): static
    {
        $this->data_stop = $data_stop;

        return $this;
    }

    public function getNrKm(): ?float
    {
        return $this->nr_km;
    }

    public function setNrKm(?float $nr_km): static
    {
        $this->nr_km = $nr_km;

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

    public function getFirma(): ?string
    {
        return $this->firma;
    }

    public function setFirma(string $firma): static
    {
        $this->firma = $firma;

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
            $cheltuieli->setComunitar($this);
        }

        return $this;
    }

    public function removeCheltuieli(Cheltuieli $cheltuieli): static
    {
        if ($this->cheltuielis->removeElement($cheltuieli)) {
            // set the owning side to null (unless already changed)
            if ($cheltuieli->getComunitar() === $this) {
                $cheltuieli->setComunitar(null);
            }
        }

        return $this;
    }

    public function getRemorca(): ?string
    {
        return $this->remorca;
    }

    public function setRemorca(string $remorca): static
    {
        $this->remorca = $remorca;

        return $this;
    }

    public function isCalculata(): ?bool
    {
        return $this->calculata;
    }

    public function setCalculata(bool $calculata): static
    {
        $this->calculata = $calculata;

        return $this;
    }
}
