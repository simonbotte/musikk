<?php

namespace App\Entity;

use App\Enum\SongDataName;
use App\Repository\SongRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SongRepository::class)]
class Song
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $artist = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $album = null;

    /**
     * @var Collection<int, SongData>
     */
    #[ORM\OneToMany(targetEntity: SongData::class, mappedBy: 'song', cascade: ['persist'])]
    private Collection $songData;

    /**
     * @var Collection<int, Playlist>
     */
    #[ORM\ManyToMany(targetEntity: Playlist::class, mappedBy: 'songs')]
    private Collection $playlists;

    public function __construct()
    {
        $this->songData = new ArrayCollection();
        $this->playlists = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getArtist(): ?string
    {
        return $this->artist;
    }

    public function setArtist(string $artist): static
    {
        $this->artist = $artist;

        return $this;
    }

    public function getAlbum(): ?string
    {
        return $this->album;
    }

    public function setAlbum(string $album): static
    {
        $this->album = $album;

        return $this;
    }

    /**
     * @return Collection<int, SongData>
     */
    public function getSongData(): Collection
    {
        return $this->songData;
    }

    public function addSongData(SongData $songData): static
    {
        if (!$this->songData->contains($songData)) {
            $this->songData->add($songData);
            $songData->setSong($this);
        }

        return $this;
    }

    public function removeSongData(SongData $songData): static
    {
        if ($this->songData->removeElement($songData)) {
            // set the owning side to null (unless already changed)
            if ($songData->getSong() === $this) {
                $songData->setSong(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Playlist>
     */
    public function getPlaylists(): Collection
    {
        return $this->playlists;
    }

    public function addPlaylist(Playlist $playlist): static
    {
        if (!$this->playlists->contains($playlist)) {
            $this->playlists->add($playlist);
            $playlist->addSong($this);
        }

        return $this;
    }

    public function removePlaylist(Playlist $playlist): static
    {
        if ($this->playlists->removeElement($playlist)) {
            $playlist->removeSong($this);
        }

        return $this;
    }

    public function toArray(): array
    {
        $artworks = $this->getSongData()->filter(function (SongData $songData) {
            return in_array($songData->getName(), [
                SongDataName::APPLE_MUSIC_ARTWORK,
                SongDataName::SPOTIFY_ARTWORK,
            ], true);
            
        });

        $artworkUrls = [];
        foreach ($artworks as $artwork) {
            $artworkUrls[$artwork->getName()->value] = $artwork->getValue();
        }
        return [
            'id' => $this->getId(),
            'title' => $this->getTitle(),
            'artist' => $this->getArtist(),
            'album' => $this->getAlbum(),
            'artworks' => $artworkUrls,
        ];
    }
}
