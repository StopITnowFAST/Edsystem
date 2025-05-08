<?php

namespace App\Entity;

use App\Repository\SubjectWikiFileRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SubjectWikiFileRepository::class)]
class SubjectWikiFile
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $wiki_id = null;

    #[ORM\Column(length: 20)]
    private ?string $file_type = null;

    #[ORM\Column]
    private ?int $file_id = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getWikiId(): ?int
    {
        return $this->wiki_id;
    }

    public function setWikiId(int $wiki_id): static
    {
        $this->wiki_id = $wiki_id;

        return $this;
    }

    public function getFileType(): ?string
    {
        return $this->file_type;
    }

    public function setFileType(string $file_type): static
    {
        $this->file_type = $file_type;

        return $this;
    }

    public function getFileId(): ?int
    {
        return $this->file_id;
    }

    public function setFileId(int $file_id): static
    {
        $this->file_id = $file_id;

        return $this;
    }
}
