<?php

namespace App\Entity;

use App\Repository\PlaylistRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Enum\PlaylistDataName;

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

    /**
     * @var Collection<int, Song>
     */
    #[ORM\ManyToMany(targetEntity: Song::class, inversedBy: 'playlists')]
    private Collection $songs;

    /**
     * @var Collection<int, Collaboration>
     */
    #[ORM\OneToMany(targetEntity: Collaboration::class, mappedBy: 'playlist')]
    private Collection $collaborations;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $artwork = null;

    #[ORM\Column(type: Types::DATETIMETZ_MUTABLE, nullable: true)]
    private ?\DateTime $lastModifiedDate = null;

    public function __construct()
    {
        $this->playlistData = new ArrayCollection();
        $this->songs = new ArrayCollection();
        $this->collaborations = new ArrayCollection();
    }

    public function toArray(): array
    {
        $data = [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'uuid' => $this->getUuid(),
            'user' => $this->getUser() ? $this->getUser()->getUuid() : null,
            'playlistData' => $this->getPlaylistData()->toArray(),
            'service' => $this->getPlaylistData()->filter(function (PlaylistData $data) {
                return $data->getName() === PlaylistDataName::SERVICE_NAME;
            })->first()?->getValue(),
        ];
        
        if ($this->getArtwork() !== null) {
            $data['artwork'] = $this->getArtwork();
        }
        
        return $data;
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

    /**
     * @return Collection<int, Song>
     */
    public function getSongs(): Collection
    {
        return $this->songs;
    }

    public function addSong(Song $song): static
    {
        if (!$this->songs->contains($song)) {
            $this->songs->add($song);
        }

        return $this;
    }

    public function removeSong(Song $song): static
    {
        $this->songs->removeElement($song);

        return $this;
    }

    /**
     * @return Collection<int, Collaboration>
     */
    public function getCollaborations(): Collection
    {
        return $this->collaborations;
    }

    public function addCollaboration(Collaboration $collaboration): static
    {
        if (!$this->collaborations->contains($collaboration)) {
            $this->collaborations->add($collaboration);
            $collaboration->setPlaylist($this);
        }

        return $this;
    }

    public function removeCollaboration(Collaboration $collaboration): static
    {
        if ($this->collaborations->removeElement($collaboration)) {
            // set the owning side to null (unless already changed)
            if ($collaboration->getPlaylist() === $this) {
                $collaboration->setPlaylist(null);
            }
        }

        return $this;
    }

    public function getArtwork(): ?string
    {
        return $this->artwork;
    }

    public function setArtwork(?string $artwork): static
    {
        $this->artwork = $artwork;

        return $this;
    }

    public function getLastModifiedDate(): ?\DateTime
    {
        return $this->lastModifiedDate;
    }

    public function setLastModifiedDate(?\DateTime $lastModifiedDate): static
    {
        $this->lastModifiedDate = $lastModifiedDate;

        return $this;
    }
}
