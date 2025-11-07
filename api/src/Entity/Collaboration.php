<?php

namespace App\Entity;

use App\Repository\CollaborationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CollaborationRepository::class)]
class Collaboration
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'collaborationInvitations')]
    private ?Playlist $playlist = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $uuid = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(nullable: true)]
    private ?int $addLimit = null;

    #[ORM\Column(length: 255)]
    private ?string $email = null;

    #[ORM\Column(nullable: true)]
    private ?int $addedSongsCount = null;

    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'playlistUuid' => $this->getPlaylist() ? $this->getPlaylist()->getUuid() : null,
            'uuid' => $this->getUuid(),
            'name' => $this->getName(),
            'addLimit' => $this->getAddLimit(),
            'email' => $this->getEmail(),
            'addedSongsCount' => $this->getAddedSongsCount(),
        ];
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPlaylist(): ?Playlist
    {
        return $this->playlist;
    }

    public function setPlaylist(?Playlist $playlist): static
    {
        $this->playlist = $playlist;

        return $this;
    }

    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    public function setUuid(string $uuid): static
    {
        $this->uuid = $uuid;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getAddLimit(): ?int
    {
        return $this->addLimit;
    }

    public function setAddLimit(?int $addLimit): static
    {
        $this->addLimit = $addLimit;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getAddedSongsCount(): ?int
    {
        return $this->addedSongsCount;
    }

    public function setAddedSongsCount(?int $addedSongsCount): static
    {
        $this->addedSongsCount = $addedSongsCount;

        return $this;
    }
}
