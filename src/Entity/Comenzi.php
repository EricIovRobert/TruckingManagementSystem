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

    public function __construct()
    {
        $this->tururis = new ArrayCollection();
        $this->retururis = new ArrayCollection();
        $this->rezolvat = false; // Setăm valoarea implicită în constructor
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
        $totalCheltuieli += $cheltuiala->getSuma() ?? 0;
    }

    $this->profit = $totalTururi + $totalRetururi - $totalCheltuieli;
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
}