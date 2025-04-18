<?php

namespace App\Entity;

use App\Repository\TestShuffleRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TestShuffleRepository::class)]
class TestShuffle
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

    #[ORM\Column(length: 40)]
    private ?string $time_end = null;

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

    public function getTimeEnd(): ?string
    {
        return $this->time_end;
    }

    public function setTimeEnd(string $time_end): static
    {
        $this->time_end = $time_end;

        return $this;
    }
}
