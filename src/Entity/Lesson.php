<?php

namespace App\Entity;

use App\Repository\LessonRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LessonRepository::class)]
class Lesson
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $subject_id = null;

    #[ORM\Column]
    private ?int $day_id = null;

    #[ORM\Column(length: 255)]
    private ?string $teacher = null;

    #[ORM\Column(length: 255)]
    private ?string $cabinet = null;

    #[ORM\Column(length: 5)]
    private ?string $start_time = null;

    #[ORM\Column(length: 5)]
    private ?string $end_time = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSubjectId(): ?int
    {
        return $this->subject_id;
    }

    public function setSubjectId(int $subject_id): static
    {
        $this->subject_id = $subject_id;

        return $this;
    }

    public function getDayId(): ?int
    {
        return $this->day_id;
    }

    public function setDayId(int $day_id): static
    {
        $this->day_id = $day_id;

        return $this;
    }

    public function getTeacher(): ?string
    {
        return $this->teacher;
    }

    public function setTeacher(string $teacher): static
    {
        $this->teacher = $teacher;

        return $this;
    }

    public function getCabinet(): ?string
    {
        return $this->cabinet;
    }

    public function setCabinet(string $cabinet): static
    {
        $this->cabinet = $cabinet;

        return $this;
    }

    public function getStartTime(): ?string
    {
        return $this->start_time;
    }

    public function setStartTime(string $start_time): static
    {
        $this->start_time = $start_time;

        return $this;
    }

    public function getEndTime(): ?string
    {
        return $this->end_time;
    }

    public function setEndTime(string $end_time): static
    {
        $this->end_time = $end_time;

        return $this;
    }
}
