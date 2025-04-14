<?php

namespace App\Entity;

use App\Repository\DayRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DayRepository::class)]
class Day
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $day_number = null;

    #[ORM\Column]
    private ?int $schedule_id = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDayNumber(): ?int
    {
        return $this->day_number;
    }

    public function setDayNumber(int $day_number): static
    {
        $this->day_number = $day_number;

        return $this;
    }

    public function getScheduleId(): ?int
    {
        return $this->schedule_id;
    }

    public function setScheduleId(int $schedule_id): static
    {
        $this->schedule_id = $schedule_id;

        return $this;
    }
}
