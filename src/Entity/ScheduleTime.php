<?php

namespace App\Entity;

use App\Repository\ScheduleTimeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ScheduleTimeRepository::class)]
class ScheduleTime
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $lesson_number = null;

    #[ORM\Column(length: 10)]
    private ?string $start_time = null;

    #[ORM\Column(length: 10)]
    private ?string $end_time = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLessonNumber(): ?int
    {
        return $this->lesson_number;
    }

    public function setLessonNumber(int $lesson_number): static
    {
        $this->lesson_number = $lesson_number;

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
