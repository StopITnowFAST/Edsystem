<?php

namespace App\Entity;

use App\Repository\TeacherRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TeacherRepository::class)]
class Teacher
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;
    
    #[ORM\Column(nullable: true)]
    private ?int $user_id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $first_name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $middle_name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $last_name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $grade = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $position = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $institut = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $division = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    private ?int $status = null;

    #[ORM\Column(length: 50)]
    private ?string $teacher_token = null;

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

    public function getFirstName(): ?string
    {
        return $this->first_name;
    }

    public function setFirstName(?string $first_name): static
    {
        $this->first_name = $first_name;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->last_name;
    }

    public function setLastName(?string $last_name): static
    {
        $this->last_name = $last_name;

        return $this;
    }

    public function getMiddleName(): ?string
    {
        return $this->middle_name;
    }

    public function setMiddleName(?string $middle_name): static
    {
        $this->middle_name = $middle_name;

        return $this;
    }

    public function getGrade(): ?string
    {
        return $this->grade;
    }

    public function setGrade(?string $grade): static
    {
        $this->grade = $grade;

        return $this;
    }

    public function getPosition(): ?string
    {
        return $this->position;
    }

    public function setPosition(?string $position): static
    {
        $this->position = $position;

        return $this;
    }

    public function getInstitut(): ?string
    {
        return $this->institut;
    }

    public function setInstitut(?string $institut): static
    {
        $this->institut = $institut;

        return $this;
    }

    public function getDivision(): ?string
    {
        return $this->division;
    }

    public function setDivision(?string $division): static
    {
        $this->division = $division;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(int $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getTeacherToken(): ?string
    {
        return $this->teacher_token;
    }

    public function setTeacherToken(string $teacher_token): static
    {
        $this->teacher_token = $teacher_token;

        return $this;
    }
}
