<?php

namespace App\Entity;

use App\Repository\HeaderMenuRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: HeaderMenuRepository::class)]
class HeaderMenu
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $parent_id = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $url = null;

    #[ORM\Column(nullable: true)]
    private ?int $place_order = null;

    #[ORM\Column]
    private ?int $status = null;

    #[ORM\Column(length: 100)]
    private ?string $name = null;

    #[ORM\Column]
    private ?bool $isForTeacher = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getParentId(): ?int
    {
        return $this->parent_id;
    }

    public function setParentId(int $parent_id): static
    {
        $this->parent_id = $parent_id;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): static
    {
        $this->url = $url;

        return $this;
    }

    public function getPlaceOrder(): ?int
    {
        return $this->place_order;
    }

    public function setPlaceOrder(?int $place_order): static
    {
        $this->place_order = $place_order;

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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function isForTeacher(): ?bool
    {
        return $this->isForTeacher;
    }

    public function setForTeacher(bool $isForTeacher): static
    {
        $this->isForTeacher = $isForTeacher;

        return $this;
    }
}
