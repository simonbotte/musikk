<?php

namespace App\Entity;

use App\Repository\PlaylistRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PlaylistRepository::class)]
class Playlist
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\ManyToOne(inversedBy: 'playlists')]
    private ?User $user = null;

    /**
     * @var Collection<int, PlaylistData>
     */
    #[ORM\OneToMany(targetEntity: PlaylistData::class, mappedBy: 'playlist', cascade: ['remove'])]
    private Collection $playlistData;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $uuid = null;

    public function __construct()
    {
        $this->playlistData = new ArrayCollection();
    }

    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'uuid' => $this->getUuid(),
            'user' => $this->getUser() ? $this->getUser()->getUuid() : null,
            'playlistData' => $this->getPlaylistData()->toArray(),
        ];
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return Collection<int, PlaylistData>
     */
    public function getPlaylistData(): Collection
    {
        return $this->playlistData;
    }

    public function addPlaylistData(PlaylistData $playlistData): static
    {
        if (!$this->playlistData->contains($playlistData)) {
            $this->playlistData->add($playlistData);
            $playlistData->setPlaylist($this);
        }

        return $this;
    }

    public function removePlaylistData(PlaylistData $playlistData): static
    {
        if ($this->playlistData->removeElement($playlistData)) {
            // set the owning side to null (unless already changed)
            if ($playlistData->getPlaylist() === $this) {
                $playlistData->setPlaylist(null);
            }
        }

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
}
