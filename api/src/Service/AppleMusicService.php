<?php

namespace App\Service;

use App\Entity\Playlist;
use App\Entity\Song;
use App\Entity\SongData;
use App\Entity\User;
use App\Repository\SongRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\PersistentCollection;
use Firebase\JWT\JWT;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use PlaylistDataName;
use ServiceName;
use SongDataName;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;
use UserDataName;

class AppleMusicService
{

    private Client $client;

    public function __construct(
        #[Autowire('%appleMusicPrivateKey%')] private string $privateKey,
        #[Autowire('%appleMusicKeyId%')] private string $keyId,
        #[Autowire('%appleMusicTeamId%')] private string $teamId,
        private UserDataService $userDataService,
        private PlaylistService $playlistService,
        private PlaylistDataService $playlistDataService,
        private EntityManagerInterface $em,
        private SongDataService $songDataService,
        private SongService $songService,
        private SongRepository $songRepository,
    ) {
        $this->client = new Client([
            'base_uri' => 'https://api.music.apple.com/v1/',
            'timeout' => 5.0,
        ]);
    }

    public function generateToken(int $time = 360): string
    {
        $token = [
            'iss' => $this->teamId,
            'iat' => time(),
            'exp' => time() + $time,
        ];

        $jwt = JWT::encode($token, $this->privateKey, 'ES256', $this->keyId);
        return $jwt;
    }

    public function getUserToken(User $user): string|null
    {
        $userData = $user->getUserData()->filter(function ($data) {
            return $data->getName() === UserDataName::APPLE_MUSIC_USER_TOKEN;
        })->first();

        if ($userData) {
            return $userData->getValue();
        }

        return null;
    }

    public function getPlaylists(User $user)
    {
        $userToken = $this->userDataService->getData($user, UserDataName::APPLE_MUSIC_USER_TOKEN);
        $developerToken = $this->generateToken();
        $headers = [
            'Authorization' => 'Bearer ' . $developerToken,
            'Music-User-Token' => $userToken,
        ];
        $response = $this->client->get('me/library/playlists', [
            'headers' => $headers
        ]);

        $apiPlaylists = json_decode((string) $response->getBody(), true)['data'] ?? [];
        $apiPlaylistIds = [];
        foreach ($apiPlaylists as $playlist) {
            if (isset($playlist['id'], $playlist['attributes']['name'], $playlist['attributes']['canEdit']) && $playlist['attributes']['canEdit'] === true) {
                $apiPlaylistIds[$playlist['id']] = $playlist['attributes']['name'];
            }
        }

        $localPlaylists = $this->playlistService
            ->getUserPlaylistFromService($user, ServiceName::APPLE_MUSIC);
        $localPlaylistWithApiIds = [];
        foreach ($localPlaylists as $playlist) {
            $playlistId = $this->playlistDataService->getData($playlist, PlaylistDataName::APPLE_MUSIC_PLAYLIST_ID);
            $localPlaylistWithApiIds[$playlistId] = $playlist;
        }

        $toAddIds    = array_diff_key($apiPlaylistIds, $localPlaylistWithApiIds);
        $toRemoveIds    = array_diff_key($localPlaylistWithApiIds, $apiPlaylistIds);
        $commonIds   = array_intersect_key($apiPlaylistIds, $localPlaylistWithApiIds);

        foreach ($toAddIds as $appleMusicId => $name) {
            $playlist = new Playlist();
            $playlist->setUser($user)
                ->setName($name)
                ->setUuid(Uuid::v4());
            $this->em->persist($playlist);
            $this->playlistDataService->saveData($playlist, PlaylistDataName::APPLE_MUSIC_PLAYLIST_ID, $appleMusicId);
            $this->playlistDataService->saveData($playlist, PlaylistDataName::SERVICE_NAME, ServiceName::APPLE_MUSIC);
        }

        foreach (array_keys($toRemoveIds) as $appleMusicId) {
            if ($playlist = $this->playlistService
                ->getPlaylist($user, $appleMusicId, PlaylistDataName::APPLE_MUSIC_PLAYLIST_ID)
            ) {
                $this->em->remove($playlist);
            }
        }

        foreach ($commonIds as $appleMusicId => $newName) {
            $playlistEntity = $localPlaylistWithApiIds[$appleMusicId];
            if ($playlistEntity->getName() !== $newName) {
                $playlistEntity->setName($newName);
                $this->em->persist($playlistEntity);
            }
        }
        $this->em->flush();
        return $this->playlistService->getUserPlaylistFromService($user, ServiceName::APPLE_MUSIC);
    }

    public function getPlaylistSongs(User $user, Playlist $playlist): ?PersistentCollection
    {
        $userToken = $this->userDataService->getData($user, UserDataName::APPLE_MUSIC_USER_TOKEN);
        $developerToken = $this->generateToken();
        $headers = [
            'Authorization' => 'Bearer ' . $developerToken,
            'Music-User-Token' => $userToken,
        ];
        $appleMusicPlaylistId = $this->playlistDataService->getData($playlist, PlaylistDataName::APPLE_MUSIC_PLAYLIST_ID);
        
        
        $response = $this->client->get('me/library/playlists/'. $appleMusicPlaylistId . '/tracks', [
            'headers' => $headers
        ]);
        
        $apiPlaylist = json_decode((string) $response->getBody()->getContents(), true)['data'] ?? null;
        if ($apiPlaylist === null) {
            throw new \RuntimeException('Invalid response from Spotify API');
        }
        
        $apiPlaylistSong = [];
        foreach ($apiPlaylist as $item) {
            $apiPlaylistSong[$item['id']] = [
                'id' => $item['id'],
                'title' => $item['attributes']['name'],
                'artist' => $item['attributes']['artistName'],
                'album' => $item['attributes']['albumName'],
            ];
        }

        $localPlaylistSongs = [];
        foreach ($playlist->getSongs() as $song) {
            $songId = $this->songDataService->getData($song,SongDataName::APPLE_MUSIC_SONG_ID);
            $localPlaylistSongs[$songId] = [
                'id' => $song->getId(),
                'title' => $song->getTitle(),
                'artist' => $song->getArtist(),
                'album' => $song->getAlbum(),
            ];
        }

        $toAddSongs = array_diff_key($apiPlaylistSong, $localPlaylistSongs);
        $toRemoveSongs = array_diff_key($localPlaylistSongs, $apiPlaylistSong);

        foreach ($toAddSongs as $appleMusicId => $songData) {
            $song = $this->songService->getSong($appleMusicId, SongDataName::APPLE_MUSIC_SONG_ID);
            if (!$song) {
                $song = $this->songRepository->findOneBy([
                    'title' => $songData['title'],
                    'artist' => $songData['artist'] ?? 'Unknown Artist',
                    'album' => $songData['album'] ?? 'Unknown Album',
                ]);
                if ($song) {
                    $songData = new SongData();
                    $songData->setSong($song)
                        ->setName(SongDataName::APPLE_MUSIC_SONG_ID)
                        ->setValue($appleMusicId);
                    $this->em->persist($songData);
                }
            }
            if (!$song) {
                $song = new Song();
                $song->setTitle($songData['title'])
                    ->setArtist($songData['artist'] ?? 'Unknown Artist')
                    ->setAlbum($songData['album'] ?? 'Unknown Album');
                $songData = new SongData();
                $songData->setSong($song)
                    ->setName(SongDataName::APPLE_MUSIC_SONG_ID)
                    ->setValue($appleMusicId);
                $this->em->persist($songData);
                $this->em->persist($song);
            }
            $playlist->addSong($song);
            $this->em->flush();
        }

        foreach (array_keys($toRemoveSongs) as $appleMusicId) {
            $song = $this->songService->getSong($appleMusicId, SongDataName::APPLE_MUSIC_SONG_ID);
            if ($song) {
                $playlist->removeSong($song);
            }
        }    
        return $playlist->getSongs();
    }

    public function searchSong(string $q, string $storefront = "fr"): array
    {
        $developerToken = $this->generateToken();
        $headers = [
            'Authorization' => 'Bearer ' . $developerToken,
        ];
        $urlQuery = http_build_query([
            'term' => $q,
            'types' => 'songs',
            'limit' => 25,
            'l' => $storefront,
            'with' => 'topResults',
        ]);
        $response = $this->client->get('catalog/'.$storefront.'/search?' . $urlQuery, [
            'headers' => $headers
        ]);
        $apiResponse = json_decode((string) $response->getBody(), true);
        if (!isset($apiResponse['results']['songs']['data'])) {
            return [];
        }
        $songs = $apiResponse['results']['songs']['data'];
        $formattedSongs = [];
        foreach ($songs as $song) {
            $formattedSongs[] = [
                'id' => $song['id'],
                'title' => $song['attributes']['name'],
                'artist' => $song['attributes']['artistName'],
                'album' => $song['attributes']['albumName'] ?? 'Unknown Album',
            ];
        }
        return $formattedSongs;
    }
}
