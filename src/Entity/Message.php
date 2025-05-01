<?php

namespace App\Entity;

use App\Repository\MessageRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MessageRepository::class)]
class Message
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $from_user_id = null;

    #[ORM\Column]
    private ?int $to_user_id = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $text = null;

    #[ORM\Column(nullable: true)]
    private ?int $file_id = null;

    #[ORM\Column]
    private ?int $date = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFromUserId(): ?int
    {
        return $this->from_user_id;
    }

    public function setFromUserId(int $from_user_id): static
    {
        $this->from_user_id = $from_user_id;

        return $this;
    }

    public function getToUserId(): ?int
    {
        return $this->to_user_id;
    }

    public function setToUserId(int $to_user_id): static
    {
        $this->to_user_id = $to_user_id;

        return $this;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(?string $text): static
    {
        $this->text = $text;

        return $this;
    }

    public function getFileId(): ?int
    {
        return $this->file_id;
    }

    public function setFileId(?int $file_id): static
    {
        $this->file_id = $file_id;

        return $this;
    }

    public function getDate(): ?int
    {
        return $this->date;
    }

    public function setDate(int $date): static
    {
        $this->date = $date;

        return $this;
    }
}
