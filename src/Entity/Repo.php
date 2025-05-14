<?php

namespace App\Entity;

use App\Repository\RepoRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: RepoRepository::class)]
class Repo
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $projectname = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $fechaInicio = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $fechaFin = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $file = null;

    #[ORM\ManyToOne(inversedBy: 'repos')]
    private ?User $owner = null;

    #[ORM\Column(length: 255)]
    private ?string $fileName = null;

    #[ORM\ManyToOne(targetEntity: Client::class)]
    private ?Client $client = null;
    
    #[ORM\ManyToMany(targetEntity: User::class)]
    #[ORM\JoinTable(name: 'repo_colaboradores')]
    private Collection $colaboradores;
    
    public function __construct()
    {
        $this->colaboradores = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProjectname(): ?string
    {
        return $this->projectname;
    }

    public function setProjectname(string $projectname): static
    {
        $this->projectname = $projectname;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getFechaInicio(): ?\DateTimeInterface
    {
        return $this->fechaInicio;
    }

    public function setFechaInicio(\DateTimeInterface $fechaInicio): static
    {
        $this->fechaInicio = $fechaInicio;

        return $this;
    }

    public function getFechaFin(): ?\DateTimeInterface
    {
        return $this->fechaFin;
    }

    public function setFechaFin(?\DateTimeInterface $fechaFin): static
    {
        $this->fechaFin = $fechaFin;

        return $this;
    }

    public function getFile(): ?string
    {
        return $this->file;
    }

    public function setFile(?string $file): static
    {
        $this->file = $file;

        return $this;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): static
    {
        $this->owner = $owner;

        return $this;
    }

    public function getFileName(): ?string
    {
        return $this->fileName;
    }

    public function setFileName(string $fileName): static
    {
        $this->fileName = $fileName;

        return $this;
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(?Client $client): static
    {
        $this->client = $client;

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getColaboradores(): Collection
    {
        return $this->colaboradores;
    }

    public function addColaborador(User $colaborador): static
    {
        if (!$this->colaboradores->contains($colaborador)) {
            $this->colaboradores->add($colaborador);
        }

        return $this;
    }

    public function removeColaborador(User $colaborador): static
    {
        $this->colaboradores->removeElement($colaborador);

        return $this;
    }
}
