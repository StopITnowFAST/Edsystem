<?php

namespace App\Entity;

use App\Repository\TestGradeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TestGradeRepository::class)]
class TestGrade
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
    private ?int $right_answers = null;

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

    public function getRightAnswers(): ?int
    {
        return $this->right_answers;
    }

    public function setRightAnswers(int $right_answers): static
    {
        $this->right_answers = $right_answers;

        return $this;
    }
}
