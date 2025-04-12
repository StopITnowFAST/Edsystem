<?php

namespace App\Entity;

use App\Repository\TestRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TestRepository::class)]
class Test
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $name = null;

    #[ORM\Column(nullable: true)]
    private ?int $status = null;

    #[ORM\Column]
    private ?int $created_at = null;

    #[ORM\Column]
    private ?int $updated_at = null;

    #[ORM\Column(length: 1)]
    private ?string $shuffle = null;

    #[ORM\Column(nullable: true)]
    private ?int $time = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $student_groups = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(?int $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getCreatedAt(): ?int
    {
        return $this->created_at;
    }

    public function setCreatedAt(int $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }

    public function getUpdatedAt(): ?int
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(int $updated_at): static
    {
        $this->updated_at = $updated_at;

        return $this;
    }

    public function getShuffle(): ?string
    {
        return $this->shuffle;
    }

    public function setShuffle(string $shuffle): static
    {
        $this->shuffle = $shuffle;

        return $this;
    }

    public function getTime(): ?int
    {
        return $this->time;
    }

    public function setTime(?int $time): static
    {
        $this->time = $time;

        return $this;
    }

    public function getStudentGroups(): ?string
    {
        return $this->student_groups;
    }

    public function setStudentGroups(string $student_groups): static
    {
        $this->student_groups = $student_groups;

        return $this;
    }
}
