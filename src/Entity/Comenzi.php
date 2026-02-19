<?php

namespace App\Entity;

use App\Repository\ComenziRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $parcAutoNrSnapshot = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $nrAccidentAuto = null;

    #[ORM\Column(length: 100)]
    private ?string $sofer = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $dataStart = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dataStop = null;

    #[ORM\Column(nullable: true)]
    private ?float $numarKm = null;

    #[ORM\Column(nullable: true)]
    private ?float $profit = null;

    /**
     * @var Collection<int, Tururi>
     */
    #[ORM\OneToMany(targetEntity: Tururi::class, mappedBy: 'comanda', cascade: ['remove'])]
    private Collection $tururis;

    /**
     * @var Collection<int, Retururi>
     */
    #[ORM\OneToMany(targetEntity: Retururi::class, mappedBy: 'comanda', cascade: ['remove'])]
    private Collection $retururis;

    #[ORM\Column]
    private ?bool $rezolvat = false; // Setăm valoarea implicită la false

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $observatii = null;

    /**
     * @var Collection<int, Cheltuieli>
     */
    #[ORM\OneToMany(targetEntity: Cheltuieli::class, mappedBy: 'comanda', cascade: ['remove'])]
    private Collection $cheltuielis;

    #[ORM\Column]
    private ?bool $calculata = false;

    #[ORM\Column]
    private ?bool $decont = false;

    #[ORM\Column]
    private ?bool $facturat = false;

    #[ORM\Column(length: 100)]
    private ?string $nr_remorca = null;

    public function __construct()
    {
        $this->tururis = new ArrayCollection();
        $this->retururis = new ArrayCollection();
        $this->rezolvat = false; // Setăm valoarea implicită în constructor
        $this->calculata = false; 
        $this->decont = false; 
        $this->facturat = false; 
        $this->cheltuielis = new ArrayCollection();
    }
    public function calculateAndSetProfit(): void
{
    $totalTururi = 0;
    foreach ($this->tururis as $tur) {
        $totalTururi += $tur->getPret() ?? 0;
    }

    $totalRetururi = 0;
    foreach ($this->retururis as $retur) {
        $totalRetururi += $retur->getPret() ?? 0;
    }

    $totalCheltuieli = 0;
foreach ($this->cheltuielis as $cheltuiala) {
    $sumaBruta = $cheltuiala->getSuma() ?? 0;
    $tvaProcent = $cheltuiala->getTva() ?? 0;
    $comisionTvaProcent = $cheltuiala->getComisionTva() ?? 0;
    $comisionTaxaDrumProcent = $cheltuiala->getComisionTaxaDrum() ?? 0;

    // Comision taxa de drum = procent din suma bruta
    $comisionTaxaDrum = $sumaBruta * ($comisionTaxaDrumProcent / 100);

    if ($tvaProcent > 0) {
        // Calculăm TVA-ul inclus în suma brută
        $tvaValue = $sumaBruta * $tvaProcent / (100 + $tvaProcent);
        // Calculăm comisionul aplicat pe TVA-ul recuperabil
        $comisionTva = $tvaValue * ($comisionTvaProcent / 100);
        // Cheltuiala netă ajustată: suma brută - TVA recuperabil + comision + comision taxa drum
        $cheltuialaNeta = $sumaBruta - $tvaValue + $comisionTva + $comisionTaxaDrum;
        $totalCheltuieli += $cheltuialaNeta;
    } else {
        // Dacă nu are TVA, folosim suma brută + comision taxa drum
        $totalCheltuieli += $sumaBruta + $comisionTaxaDrum;
    }
}
    $this->profit = $totalTururi + $totalRetururi - $totalCheltuieli;
}

public function updateFacturatStatus(): void
{
    $allFacturat = true;
    $hasItems = false;

    if (!$this->tururis->isEmpty()) {
        $hasItems = true;
        foreach ($this->tururis as $tur) {
            if (!$tur->isFacturat()) {
                $allFacturat = false;
                break;
            }
        }
    }

    if ($allFacturat && !$this->retururis->isEmpty()) {
        $hasItems = true;
        foreach ($this->retururis as $retur) {
            if (!$retur->isFacturat()) {
                $allFacturat = false;
                break;
            }
        }
    }
    
    if ($hasItems) {
        $this->facturat = $allFacturat;
    }
}

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

        if ($parcAuto) {
            $this->parcAutoNrSnapshot = $parcAuto->getNrAuto();
        }

        return $this;
    }

    public function getParcAutoNrSnapshot(): ?string
    {
        return $this->parcAutoNrSnapshot;
    }

    public function setParcAutoNrSnapshot(?string $parcAutoNrSnapshot): static
    {
        $this->parcAutoNrSnapshot = $parcAutoNrSnapshot;

        return $this;
    }

    public function getNrAccidentAuto(): ?string
    {
        return $this->nrAccidentAuto;
    }

    public function setNrAccidentAuto(?string $nrAccidentAuto): static
    {
        $this->nrAccidentAuto = $nrAccidentAuto;

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

    public function setDataStop(?\DateTimeInterface $dataStop): static
    {
        $this->dataStop = $dataStop;

        return $this;
    }

    public function getNumarKm(): ?float
    {
        return $this->numarKm;
    }

    public function setNumarKm(?float $numarKm): static
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

    /**
     * @return Collection<int, Tururi>
     */
    public function getTururis(): Collection
    {
        return $this->tururis;
    }

    public function addTururi(Tururi $tururi): static
    {
        if (!$this->tururis->contains($tururi)) {
            $this->tururis->add($tururi);
            $tururi->setComanda($this);
        }

        return $this;
    }

    public function removeTururi(Tururi $tururi): static
    {
        if ($this->tururis->removeElement($tururi)) {
            // set the owning side to null (unless already changed)
            if ($tururi->getComanda() === $this) {
                $tururi->setComanda(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Retururi>
     */
    public function getRetururis(): Collection
    {
        return $this->retururis;
    }

    public function addRetururi(Retururi $retururi): static
    {
        if (!$this->retururis->contains($retururi)) {
            $this->retururis->add($retururi);
            $retururi->setComanda($this);
        }

        return $this;
    }

    public function removeRetururi(Retururi $retururi): static
    {
        if ($this->retururis->removeElement($retururi)) {
            // set the owning side to null (unless already changed)
            if ($retururi->getComanda() === $this) {
                $retururi->setComanda(null);
            }
        }

        return $this;
    }

    public function isRezolvat(): ?bool
    {
        return $this->rezolvat;
    }

    public function setRezolvat(bool $rezolvat): static
    {
        $this->rezolvat = $rezolvat;

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
            $cheltuieli->setComanda($this);
        }

        return $this;
    }

    public function removeCheltuieli(Cheltuieli $cheltuieli): static
    {
        if ($this->cheltuielis->removeElement($cheltuieli)) {
            // set the owning side to null (unless already changed)
            if ($cheltuieli->getComanda() === $this) {
                $cheltuieli->setComanda(null);
            }
        }

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

    public function isDecont(): ?bool
    {
        return $this->decont;
    }

    public function setDecont(bool $decont): static
    {
        $this->decont = $decont;

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

    public function getNrRemorca(): ?string
    {
        return $this->nr_remorca;
    }

    public function setNrRemorca(string $nr_remorca): static
    {
        $this->nr_remorca = $nr_remorca;

        return $this;
    }
}