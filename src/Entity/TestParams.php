<?php

namespace App\Entity;

use App\Repository\TestParamsRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TestParamsRepository::class)]
class TestParams
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 30)]
    private ?string $user_id = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $shuffle_seed = null;

    #[ORM\Column]
    private ?int $time_start = null;

    #[ORM\Column]
    private ?int $time_end = null;

    #[ORM\Column]
    private ?int $attempt = null;

    #[ORM\Column]
    private ?int $test_id = null;

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

    public function getShuffleSeed(): ?string
    {
        return $this->shuffle_seed;
    }

    public function setShuffleSeed(string $shuffle_seed): static
    {
        $this->shuffle_seed = $shuffle_seed;

        return $this;
    }

    public function getTimeStart(): ?int
    {
        return $this->time_start;
    }

    public function setTimeStart(int $time_start): static
    {
        $this->time_start = $time_start;

        return $this;
    }

    public function getTimeEnd(): ?int
    {
        return $this->time_end;
    }

    public function setTimeEnd(int $time_end): static
    {
        $this->time_end = $time_end;

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

    public function getTestId(): ?int
    {
        return $this->test_id;
    }

    public function setTestId(int $test_id): static
    {
        $this->test_id = $test_id;

        return $this;
    }
}
