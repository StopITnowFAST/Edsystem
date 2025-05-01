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

    #[ORM\Column(length: 1)]
    private ?string $shuffle = null;

    #[ORM\Column(nullable: true)]
    private ?string $time = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $student_groups = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(nullable: true)]
    private ?int $attempts = null;

    #[ORM\Column]
    private ?int $points_for_3 = null;

    #[ORM\Column]
    private ?int $points_for_4 = null;

    #[ORM\Column]
    private ?int $points_for_5 = null;

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

    public function getShuffle(): ?string
    {
        return $this->shuffle;
    }

    public function setShuffle(string $shuffle): static
    {
        $this->shuffle = $shuffle;

        return $this;
    }

    public function getTime(): ?string
    {
        return $this->time;
    }

    public function setTime(?string $time): static
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getAttempts(): ?int
    {
        return $this->attempts;
    }

    public function setAttempts(?int $attempts): static
    {
        $this->attempts = $attempts;

        return $this;
    }

    public function getPointsFor3(): ?int
    {
        return $this->points_for_3;
    }

    public function setPointsFor3(int $points_for_3): static
    {
        $this->points_for_3 = $points_for_3;

        return $this;
    }

    public function getPointsFor4(): ?int
    {
        return $this->points_for_4;
    }

    public function setPointsFor4(int $points_for_4): static
    {
        $this->points_for_4 = $points_for_4;

        return $this;
    }

    public function getPointsFor5(): ?int
    {
        return $this->points_for_5;
    }

    public function setPointsFor5(int $points_for_5): static
    {
        $this->points_for_5 = $points_for_5;

        return $this;
    }
}
