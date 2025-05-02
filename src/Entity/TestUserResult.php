<?php

namespace App\Entity;

use App\Repository\TestUserResultRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TestUserResultRepository::class)]
class TestUserResult
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $user_id = null;

    #[ORM\Column]
    private ?int $test_id = null;

    #[ORM\Column]
    private ?int $grade = null;

    #[ORM\Column]
    private ?int $attempt = null;

    #[ORM\Column]
    private ?int $right_answers = null;

    #[ORM\Column(nullable: true)]
    private ?int $total_points = null;

    #[ORM\Column(nullable: true)]
    private ?int $completed_at = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getTestId(): ?int
    {
        return $this->test_id;
    }

    public function setTestId(int $test_id): static
    {
        $this->test_id = $test_id;

        return $this;
    }

    public function getGrade(): ?int
    {
        return $this->grade;
    }

    public function setGrade(int $grade): static
    {
        $this->grade = $grade;

        return $this;
    }

    public function getAttempt(): ?int
    {
        return $this->attempt;
    }

    public function setAttempt(int $attempt): static
    {
        $this->attempt = $attempt;

        return $this;
    }

    public function getRightAnswers(): ?int
    {
        return $this->right_answers;
    }

    public function setRightAnswers(int $right_answers): static
    {
        $this->right_answers = $right_answers;

        return $this;
    }

    public function getTotalPoints(): ?int
    {
        return $this->total_points;
    }

    public function setTotalPoints(?int $total_points): static
    {
        $this->total_points = $total_points;

        return $this;
    }

    public function getCompletedAt(): ?int
    {
        return $this->completed_at;
    }

    public function setCompletedAt(?int $completed_at): static
    {
        $this->completed_at = $completed_at;

        return $this;
    }
}
