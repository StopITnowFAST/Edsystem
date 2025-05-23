<?php

namespace App\Entity;

use App\Repository\SubjectWikiRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SubjectWikiRepository::class)]
#[ORM\HasLifecycleCallbacks]
class SubjectWiki
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $text = null;

    #[ORM\Column(nullable: true)]
    private ?int $subject_id = null;

    #[ORM\Column]
    private ?bool $can_upload_file = null;

    #[ORM\Column]
    private ?int $created_at = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getSubjectId(): ?int
    {
        return $this->subject_id;
    }

    public function setSubjectId(?int $subject_id): static
    {
        $this->subject_id = $subject_id;

        return $this;
    }

    public function isCanUploadFile(): ?bool
    {
        return $this->can_upload_file;
    }

    public function setCanUploadFile(bool $can_upload_file): static
    {
        $this->can_upload_file = $can_upload_file;

        return $this;
    }

    public function getCreatedAt(): ?int
    {
        return $this->created_at;
    }

    public function setCreatedAt(int $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function updateTimestamps()
    {
        if ($this->getCreatedAt() === null) {
            $this->setCreatedAt(time());
        }
    }
}
