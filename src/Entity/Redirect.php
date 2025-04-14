<?php

namespace App\Entity;

use App\Repository\RedirectRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RedirectRepository::class)]
class Redirect
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $redirect_from = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $redirect_to = null;

    #[ORM\Column]
    private ?int $status = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getRedirectFrom(): ?string
    {
        return $this->redirect_from;
    }

    public function setRedirectFrom(string $redirect_from): static
    {
        $this->redirect_from = $redirect_from;

        return $this;
    }

    public function getRedirectTo(): ?string
    {
        return $this->redirect_to;
    }

    public function setRedirectTo(string $redirect_to): static
    {
        $this->redirect_to = $redirect_to;

        return $this;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(int $status): static
    {
        $this->status = $status;

        return $this;
    }
}
