<?php

namespace App\Entity;

use App\Repository\CasaExpeditiiRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CasaExpeditiiRepository::class)]
class CasaExpeditii
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $numeClient = null;

    #[ORM\Column(length: 255)]
    private ?string $nrComandaClient = null;

    #[ORM\Column]
    private ?float $pretClient = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nrFacturaClient = null;

    #[ORM\Column(length: 255)]
    private ?string $numeTransportator = null;

    #[ORM\Column]
    private ?float $pretTransportator = null;

    #[ORM\Column(length: 255)]
    private ?string $nrComandaTransportator = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $contractPath = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumeClient(): ?string
    {
        return $this->numeClient;
    }

    public function setNumeClient(string $numeClient): static
    {
        $this->numeClient = $numeClient;

        return $this;
    }

    public function getNrComandaClient(): ?string
    {
        return $this->nrComandaClient;
    }

    public function setNrComandaClient(string $nrComandaClient): static
    {
        $this->nrComandaClient = $nrComandaClient;

        return $this;
    }

    public function getPretClient(): ?float
    {
        return $this->pretClient;
    }

    public function setPretClient(float $pretClient): static
    {
        $this->pretClient = $pretClient;

        return $this;
    }

    public function getNrFacturaClient(): ?string
    {
        return $this->nrFacturaClient;
    }

    public function setNrFacturaClient(?string $nrFacturaClient): static
    {
        $this->nrFacturaClient = $nrFacturaClient;

        return $this;
    }

    public function getNumeTransportator(): ?string
    {
        return $this->numeTransportator;
    }

    public function setNumeTransportator(string $numeTransportator): static
    {
        $this->numeTransportator = $numeTransportator;

        return $this;
    }

    public function getPretTransportator(): ?float
    {
        return $this->pretTransportator;
    }

    public function setPretTransportator(float $pretTransportator): static
    {
        $this->pretTransportator = $pretTransportator;

        return $this;
    }

    public function getNrComandaTransportator(): ?string
    {
        return $this->nrComandaTransportator;
    }

    public function setNrComandaTransportator(string $nrComandaTransportator): static
    {
        $this->nrComandaTransportator = $nrComandaTransportator;

        return $this;
    }

    public function getContractPath(): ?string
    {
        return $this->contractPath;
    }

    public function setContractPath(?string $contractPath): static
    {
        $this->contractPath = $contractPath;

        return $this;
    }
}
