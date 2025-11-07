<?php

namespace App\Service;

use App\Entity\Playlist;
use App\Entity\PlaylistData;
use App\Entity\User;
use App\Repository\PlaylistDataRepository;
use App\Repository\PlaylistRepository;
use App\Enum\UserDataName;
use Doctrine\Common\Collections\ArrayCollection;
use Firebase\JWT\JWT;
use App\Enum\PlaylistDataName;
use PouleR\AppleMusicAPI\AppleMusicAPI;
use Symfony\Component\DependencyInjection\Attribute\Autowire;


class PlaylistService
{
    public function __construct(
        private PlaylistRepository $playlistRepository,
        private PlaylistDataRepository $playlistDataRepository,
        private PlaylistDataService $playlistDataService,
    ) {}

    public function getPlaylistId(Playlist $playlist): string|null
    {
        $playlistData = $playlist->getPlaylistData()->filter(function ($data) {
            return $data->getName() === PlaylistDataName::APPLE_MUSIC_PLAYLIST_ID;
        })->first();

        if ($playlistData) {
            return $playlistData->getValue();
        }

        return null;
    }

    public function getPlaylist(User $user, string $playlistId, string $playlistIdDataName): Playlist|null
    {
        $playlistDataId = $this->playlistDataRepository->findOneBy([
            'name' => $playlistIdDataName,
            'value' => $playlistId,
        ]);

        if ($playlistDataId instanceof PlaylistData) {
            $playlist = $this->playlistRepository->findOneBy([
                'id' => $playlistDataId->getPlaylist()->getId(),
                'user' => $user,
            ]);
            return $playlist;
        }
        return null;
    }

    public function getUserPlaylistFromService(User $user, string $serviceName): ArrayCollection
    {
        $userPlaylist = $user->getPlaylists()->filter(function ($playlist) use ($serviceName) {
            if ($playlist->getPlaylistData()->isEmpty()) {
                return false;
            } 
            $playlistData = $this->playlistDataService->getData($playlist, PlaylistDataName::SERVICE_NAME);
            return $playlistData === $serviceName;
        });
        return $userPlaylist;
    }

    public function formatPlaylists(array $playlists): array
    {
        $formattedPlaylists = [];
        foreach ($playlists as $playlist) {
            $formattedPlaylists[] = $playlist->toArray();
        }
        return $formattedPlaylists;
    }

    public function formatPlaylist(AppleMusicAPI $api, Playlist $playlist): array
    {
        $playlistResponse = $api->getLibraryPlaylist($this->getPlaylistId($playlist), ['tracks']);
        
        $playlist = [
            'id' => $playlistResponse->data[0]->id,
            'title' => $playlistResponse->data[0]->attributes->name,
            'songs' => [],
        ];

        $songs = array_map(function ($song) {
            return [
                'id' => $song->id,
                'title' => $song->attributes->name,
                'artist' => $song->attributes->artistName,
            ];
        }, $playlistResponse->data[0]->relationships->tracks->data);
        $playlist['songs'] = $songs;

        return $playlist;
    }
}