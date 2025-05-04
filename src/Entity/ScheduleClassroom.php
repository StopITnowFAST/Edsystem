<?php

namespace App\Entity;

use App\Repository\ScheduleClassroomRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ScheduleClassroomRepository::class)]
class ScheduleClassroom
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $classroom_type = null;

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

    public function getClassroomType(): ?string
    {
        return $this->classroom_type;
    }

    public function setClassroomType(?string $classroom_type): static
    {
        $this->classroom_type = $classroom_type;

        return $this;
    }
}
