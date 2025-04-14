<?php

namespace App\Entity;

use App\Repository\FileRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FileRepository::class)]
#[ORM\HasLifecycleCallbacks]
class File
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $file_name = null;

    #[ORM\Column(length: 40)]
    private ?string $extension = null;

    #[ORM\Column(length: 40)]
    private ?string $size = null;

    #[ORM\Column(length: 40)]
    private ?string $created_by = null;

    #[ORM\Column(length: 40)]
    private ?string $created_at = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $for_groups = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $real_file_name = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFileName(): ?string
    {
        return $this->file_name;
    }

    public function setFileName(string $file_name): static
    {
        $this->file_name = $file_name;

        return $this;
    }

    public function getExtension(): ?string
    {
        return $this->extension;
    }

    public function setExtension(string $extension): static
    {
        $this->extension = $extension;

        return $this;
    }

    public function getSize(): ?string
    {
        return $this->size;
    }

    public function setSize(string $size): static
    {
        $this->size = $size;

        return $this;
    }

    public function getCreatedBy(): ?string
    {
        return $this->created_by;
    }

    public function setCreatedBy(string $created_by): static
    {
        $this->created_by = $created_by;

        return $this;
    }

    public function getCreatedAt(): ?string
    {
        return $this->created_at;
    }

    public function setCreatedAt(string $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }

    public function getForGroups(): ?string
    {
        return $this->for_groups;
    }

    public function setForGroups(?string $for_groups): static
    {
        $this->for_groups = $for_groups;

        return $this;
    }

    public function getRealFileName(): ?string
    {
        return $this->real_file_name;
    }

    public function setRealFileName(string $real_file_name): static
    {
        $this->real_file_name = $real_file_name;

        return $this;
    }

    #[ORM\PrePersist]
    public function updateTimestamps()
    {
        if ($this->getCreatedAt() === null) {
            $this->setCreatedAt(time());
        }
    }
}
