<?php

namespace App\Entity;

use App\Repository\ScheduleRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ScheduleRepository::class)]
class Schedule
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 1)]
    private ?string $week_number = null;

    #[ORM\Column(length: 1)]
    private ?string $schedule_day = null;

    #[ORM\Column]
    private ?int $schedule_time_id = null;

    #[ORM\Column]
    private ?int $schedule_lesson_type_id = null;

    #[ORM\Column]
    private ?int $schedule_classroom_id = null;

    #[ORM\Column]
    private ?int $schedule_group_id = null;

    #[ORM\Column]
    private ?int $user_id = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getWeekNumber(): ?string
    {
        return $this->week_number;
    }

    public function setWeekNumber(string $week_number): static
    {
        $this->week_number = $week_number;

        return $this;
    }

    public function getScheduleDay(): ?string
    {
        return $this->schedule_day;
    }

    public function setScheduleDay(string $schedule_day): static
    {
        $this->schedule_day = $schedule_day;

        return $this;
    }

    public function getScheduleTimeId(): ?int
    {
        return $this->schedule_time_id;
    }

    public function setScheduleTimeId(int $schedule_time_id): static
    {
        $this->schedule_time_id = $schedule_time_id;

        return $this;
    }

    public function getScheduleLessonTypeId(): ?int
    {
        return $this->schedule_lesson_type_id;
    }

    public function setScheduleLessonTypeId(int $schedule_lesson_type_id): static
    {
        $this->schedule_lesson_type_id = $schedule_lesson_type_id;

        return $this;
    }

    public function getScheduleClassroomId(): ?int
    {
        return $this->schedule_classroom_id;
    }

    public function setScheduleClassroomId(int $schedule_classroom_id): static
    {
        $this->schedule_classroom_id = $schedule_classroom_id;

        return $this;
    }

    public function getScheduleGroupId(): ?int
    {
        return $this->schedule_group_id;
    }

    public function setScheduleGroupId(int $schedule_group_id): static
    {
        $this->schedule_group_id = $schedule_group_id;

        return $this;
    }

    public function getUserId(): ?int
    {
        return $this->user_id;
    }

    public function setUserId(int $user_id): static
    {
        $this->user_id = $user_id;

        return $this;
    }
}
