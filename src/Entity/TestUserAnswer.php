<?php

namespace App\Entity;

use App\Repository\TestUserAnswerRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TestUserAnswerRepository::class)]
class TestUserAnswer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 30)]
    private ?string $user_id = null;

    #[ORM\Column]
    private ?int $question_id = null;

    #[ORM\Column]
    private ?int $answer_id = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): ?string
    {
        return $this->user_id;
    }

    public function setUserId(string $user_id): static
    {
        $this->user_id = $user_id;

        return $this;
    }

    public function getQuestionId(): ?int
    {
        return $this->question_id;
    }

    public function setQuestionId(int $question_id): static
    {
        $this->question_id = $question_id;

        return $this;
    }

    public function getAnswerId(): ?int
    {
        return $this->answer_id;
    }

    public function setAnswerId(int $answer_id): static
    {
        $this->answer_id = $answer_id;

        return $this;
    }
}
