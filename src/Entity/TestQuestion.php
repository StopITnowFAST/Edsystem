<?php

namespace App\Entity;

use App\Repository\TestQuestionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TestQuestionRepository::class)]
class TestQuestion
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $test_id = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $text = null;

    #[ORM\Column]
    private ?int $correct_answers = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(string $text): static
    {
        $this->text = $text;

        return $this;
    }

    public function getCorrectAnswers(): ?int
    {
        return $this->correct_answers;
    }

    public function setCorrectAnswers(int $correct_answers): static
    {
        $this->correct_answers = $correct_answers;

        return $this;
    }
}
