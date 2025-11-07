<?php

namespace App\Service;

use App\Entity\Playlist;
use App\Entity\PlaylistData;
use App\Entity\Song;
use App\Entity\SongData;
use App\Entity\User;
use App\Repository\SongRepository;
use App\Enum\UserDataName;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\PersistentCollection;
use Dom\Entity;
use Firebase\JWT\JWT;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use App\Enum\PlaylistDataName;
use App\Enum\ServiceName;
use App\Enum\SongDataName;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;


class SpotifyService
{

    private Client $client;

    public function __construct(
        #[Autowire('%spotifyClientId%')] private string $clientId,
        #[Autowire('%spotifyClientSecret%')] private string $clientSecret,
        private UserDataService $userDataService,
        private EntityManagerInterface $em,
        private PlaylistService $playlistService,
        private PlaylistDataService $playlistDataService,
        private SongDataService $songDataService,
        private SongService $songService,
        private SongRepository $songRepository,
    ) {
        $this->client = new Client([
            'base_uri' => 'https://api.spotify.com/v1/',
            'timeout' => 5.0,
        ]);
    }

    public function getLoginUrl(): string
    {
        $state = bin2hex(random_bytes(8));
        $scope = 'user-read-private user-read-email playlist-modify-public playlist-modify-private user-library-read user-library-modify';
        $redirect_uri = "https://musikk.localhost/login/spotify";
        $queryParams = http_build_query([
            'response_type' => 'code',
            'client_id' => $this->clientId,
            'scope' => $scope,
            'redirect_uri' => $redirect_uri,
            'state' => $state
        ]);

        return 'https://accounts.spotify.com/authorize?' . $queryParams;
    }

    public function getUserId(User $user): string|null
    {
        $userData = $user->getUserData()->filter(function ($data) {
            return $data->getName() === UserDataName::SPOTIFY_USER_ID;
        })->first();

        if ($userData) {
            return $userData->getValue();
        }
        return null;
    }

    public function getSpotifyAccessToken(string $code, string $redirectUri = "", bool $withRefreshToken = false): array
    {
        $client = new Client();
        $authHeader = base64_encode("{$this->clientId}:{$this->clientSecret}");
        try {
            $formParams = [
                'grant_type' => $withRefreshToken ? 'refresh_token' : 'authorization_code',
            ];

            if ($withRefreshToken) {
                $formParams['refresh_token'] = $code;
            } else {
                $formParams['code'] = $code;
                if (!empty($redirectUri)) {
                    $formParams['redirect_uri'] = $redirectUri;
                }
            }
            $response = $client->post('https://accounts.spotify.com/api/token', [
                'headers' => [
                    'Content-Type'  => 'application/x-www-form-urlencoded',
                    'Authorization' => 'Basic ' . $authHeader,
                ],
                'form_params' => $formParams,
            ]);

            $data = json_decode((string) $response->getBody(), true);
            return $data;
        } catch (GuzzleException $e) {
            throw new \RuntimeException('Error while fetching Spotify token: ' . $e->getMessage());
        }
    }

    public function getPlaylists(User $user, bool $retry = false)
    {
        $accessToken = $this->userDataService->getData($user, UserDataName::SPOTIFY_USER_TOKEN);
        if (!$accessToken) {
            throw new \RuntimeException('Spotify access token not found');
        }

        try {
            $response = $this->client->get('me/playlists', [
                'headers' => ['Authorization' => 'Bearer ' . $accessToken],
            ]);
            $apiPlaylists = json_decode((string) $response->getBody(), true)['items'] ?? [];
            // $apiPlaylistIds = array_column($apiPlaylists, 'name', 'id');
            $apiPlaylistIds = [];
            foreach ($apiPlaylists as $playlist) {
                if (isset($playlist['id'], $playlist['name'])) {
                    $apiPlaylistIds[$playlist['id']] = [
                        "name" => $playlist['name'],
                        "artwork" => $playlist['images'][0]['url'] ?? null
                    ];
                }
            }
            $localPlaylists = $this->playlistService
                ->getUserPlaylistFromService($user, ServiceName::SPOTIFY);
            $localPlaylistWithApiIds = [];
            foreach ($localPlaylists as $playlist) {
                $playlistId = $this->playlistDataService->getData($playlist, PlaylistDataName::SPOTIFY_PLAYLIST_ID);
                $localPlaylistWithApiIds[$playlistId] = $playlist;
            }

            $toAddIds    = array_diff_key($apiPlaylistIds, $localPlaylistWithApiIds);
            $toRemoveIds    = array_diff_key($localPlaylistWithApiIds, $apiPlaylistIds);
            $commonIds   = array_intersect_key($apiPlaylistIds, $localPlaylistWithApiIds);

            foreach ($toAddIds as $spotifyId => $playlistData) {
                $playlist = new Playlist();
                $playlist->setUser($user)
                    ->setName($playlistData['name'])
                    ->setArtwork($playlistData['artwork'])
                    ->setUuid(Uuid::v7());
                $this->em->persist($playlist);
                $this->playlistDataService->saveData($playlist, PlaylistDataName::SPOTIFY_PLAYLIST_ID, $spotifyId);
                $this->playlistDataService->saveData($playlist, PlaylistDataName::SERVICE_NAME, ServiceName::SPOTIFY);
            }

            foreach (array_keys($toRemoveIds) as $spotifyId) {
                if ($playlist = $this->playlistService
                    ->getPlaylist($user, $spotifyId, PlaylistDataName::SPOTIFY_PLAYLIST_ID)
                ) {
                    $this->em->remove($playlist);
                }
            }

            foreach ($commonIds as $spotifyId => $playlistData) {
                $playlistEntity = $localPlaylistWithApiIds[$spotifyId];
                $changed = false;
                if ($playlistEntity->getName() !== $playlistData['name']) {
                    $playlistEntity->setName($playlistData['name']);
                }

                if ($playlistEntity->getArtwork() !== $playlistData['artwork']) {
                    $playlistEntity->setArtwork($playlistData['artwork']);
                    $changed = true;
                }
                if ($changed) {
                    $this->em->persist($playlistEntity);
                }
            }
            $this->em->flush();
            return $this->playlistService->getUserPlaylistFromService($user, ServiceName::SPOTIFY);
        } catch (GuzzleException $e) {
            if ($e->getCode() === Response::HTTP_UNAUTHORIZED && !$retry) {

                $refreshToken = $this->userDataService->getData($user, UserDataName::SPOTIFY_REFRESH_TOKEN);

                if ($refreshToken) {
                    $newToken = $this->getSpotifyAccessToken($refreshToken, "", true);
                    $this->userDataService->saveData($user, UserDataName::SPOTIFY_USER_TOKEN, $newToken['access_token']);
                    $this->userDataService->saveData($user, UserDataName::SPOTIFY_REFRESH_TOKEN, $newToken['refresh_token']);
                    return $this->getPlaylists($user, true);
                }
            }
            throw new \RuntimeException('Error while fetching Spotify playlist: ' . $e->getMessage());
        }
    }

    public function getPlaylistSongs(User $user, Playlist $playlist, bool $retry = false): ?PersistentCollection
    {
        $accessToken = $this->userDataService->getData($user, UserDataName::SPOTIFY_USER_TOKEN);
        if (!$accessToken) {
            throw new \RuntimeException('Spotify access token not found');
        }
        $spotifyPlaylistId = $this->playlistDataService->getData($playlist, PlaylistDataName::SPOTIFY_PLAYLIST_ID);

        try {
            $response = $this->client->get('playlists/' . $spotifyPlaylistId, [
                'headers' => ['Authorization' => 'Bearer ' . $accessToken],
            ]);

            $apiPlaylist = json_decode((string) $response->getBody()->getContents(), true) ?? null;
            if ($apiPlaylist === null) {
                throw new \RuntimeException('Invalid response from Spotify API');
            }

            $apiPlaylistSong = [];
            foreach ($apiPlaylist['tracks']['items'] as $item) {
                $apiPlaylistSong[$item['track']['id']] = [
                    'id' => $item['track']['id'],
                    'title' => $item['track']['name'],
                    'artist' => implode(", ", array_map(function ($artist) {
                        return $artist['name'];
                    }, $item['track']['artists'])),
                    'album' => $item['track']['album']['name'] ?? null,
                    'artwork' => $item['track']['album']['images'][0]['url'] ?? null,
                ];
            }

            $localPlaylistSongs = [];
            foreach ($playlist->getSongs() as $song) {
                $songId = $this->songDataService->getData($song, SongDataName::SPOTIFY_SONG_ID);
                $localPlaylistSongs[$songId] = [
                    'id' => $song->getId(),
                    'title' => $song->getTitle(),
                    'artist' => $song->getArtist(),
                    'album' => $song->getAlbum(),
                ];
            }

            $toAddSongs = array_diff_key($apiPlaylistSong, $localPlaylistSongs);
            $toRemoveSongs = array_diff_key($localPlaylistSongs, $apiPlaylistSong);

            foreach ($toAddSongs as $spotifyId => $songData) {
                $song = $this->songService->getSong($spotifyId, SongDataName::SPOTIFY_SONG_ID);
                if (!$song) {
                    $song = $this->songRepository->findOneBy([
                        'title' => $songData['title'],
                        'artist' => $songData['artist'] ?? 'Unknown Artist',
                        'album' => $songData['album'] ?? 'Unknown Album',
                    ]);
                    if ($song) {
                        $this->songService->addServiceIdToSong(
                            $song,
                            $spotifyId,
                            SongDataName::SPOTIFY_SONG_ID,
                            false
                        );
                    }
                }
                if (!$song) {
                    $song = $this->songService->addSong(
                        $songData['title'],
                        $songData['artist'] ?? 'Unknown Artist',
                        $songData['album'] ?? 'Unknown Album',
                        $songData['artwork'] ?? null,
                        ServiceName::SPOTIFY,
                        SongDataName::SPOTIFY_SONG_ID,
                        false
                    );
                }
                $playlist->addSong($song);
                $this->em->flush();
            }

            foreach (array_keys($toRemoveSongs) as $spotifyId) {
                $song = $this->songService->getSong($spotifyId, SongDataName::SPOTIFY_SONG_ID);
                if ($song) {
                    $playlist->removeSong($song);
                }
            }
        } catch (GuzzleException $e) {
            if ($e->getCode() === Response::HTTP_UNAUTHORIZED && !$retry) {

                $refreshToken = $this->userDataService->getData($user, UserDataName::SPOTIFY_REFRESH_TOKEN);

                if ($refreshToken) {
                    $newToken = $this->getSpotifyAccessToken($refreshToken, "", true);
                    $this->userDataService->saveData($user, UserDataName::SPOTIFY_USER_TOKEN, $newToken['access_token']);
                    $this->userDataService->saveData($user, UserDataName::SPOTIFY_REFRESH_TOKEN, $newToken['refresh_token']);
                    return $this->getPlaylistSongs($user, $playlist, true);
                }
            }
            throw new \RuntimeException('Error while fetching Spotify playlist: ' . $e->getMessage());
        }
        return $playlist->getSongs();
    }

    public function searchSong(User $user,  string $q, string $market = "fr"): array
    {
        $accessToken = $this->userDataService->getData($user, UserDataName::SPOTIFY_USER_TOKEN);
        if (!$accessToken) {
            throw new \RuntimeException('Spotify access token not found');
        }

        try {
            $response = $this->client->get('search', [
                'headers' => ['Authorization' => 'Bearer ' . $accessToken],
                'query' => [
                    'q' => $q,
                    'type' => 'track',
                    'limit' => 5,
                    'market' => $market,
                ],
            ]);
            $apiSongsData = json_decode((string) $response->getBody(), true) ?? null;
            if ($apiSongsData === null || !isset($apiSongsData['tracks']['items'])) {
                throw new \RuntimeException('Invalid response from Spotify API');
            }

            $apiSongs = [];
            foreach ($apiSongsData['tracks']['items'] as $item) {
                $apiSongs[$item['track']['id']] = [
                    'id' => $item['track']['id'],
                    'title' => $item['track']['name'],
                    'artist' => implode(", ", array_map(function ($artist) {
                        return $artist['name'];
                    }, $item['track']['artists'])),
                    'album' => $item['track']['album']['name'] ?? null,
                    'artwork' => $item['track']['album']['images'][0]['url'] ?? null,
                ];
            }
            $formatedSongs = [];
            $newSongPersisted = false;
            foreach ($apiSongs as $apiSong) {
                $artistName = implode(", ", array_map(function ($artist) {
                    return $artist['name'];
                }, $apiSong['artists']));

                $song = $this->songService->getSong($apiSong['id'], SongDataName::SPOTIFY_SONG_ID);
                if ($song === null) {
                    $song = $this->songService->getSongByData(
                        $apiSong['title'],
                        $artistName,
                        $apiSong['album'],
                    );
                    if ($song !== null) {
                        $this->songService->addServiceIdToSong(
                            $song,
                            $apiSong['id'],
                            SongDataName::SPOTIFY_SONG_ID,
                            false
                        );
                        $newSongPersisted = true;
                    }
                }
                if ($song === null) {
                    $this->songService->addSong(
                        $apiSong['title'],
                        $artistName,
                        $apiSong['album'],
                        $apiSong['artwork'],
                        $apiSong['id'],

                        SongDataName::SPOTIFY_SONG_ID,
                        false
                    );
                    $newSongPersisted = true;
                }

                $formatedSongs[] = [
                    'id' => $apiSong['id'],
                    'title' => $apiSong['title'],
                    'artist' => $artistName,
                    'album' => $apiSong['album'],
                ];
            }
            if ($newSongPersisted) {
                $this->em->flush();
            }
            return $formatedSongs;
        } catch (GuzzleException $e) {
            throw new \RuntimeException('Error while searching Spotify song: ' . $e->getMessage());
        }
    }

    public function addSongToPlaylist(User $user, Playlist $playlist, Song $song): ?Song
    {
        $accessToken = $this->userDataService->getData($user, UserDataName::SPOTIFY_USER_TOKEN);
        if (!$accessToken) {
            throw new \RuntimeException('Spotify access token not found');
        }

        $spotifyPlaylistId = $this->playlistDataService->getData($playlist, PlaylistDataName::SPOTIFY_PLAYLIST_ID);
        $spotifySongId = $this->songDataService->getData($song, SongDataName::SPOTIFY_SONG_ID);
        if (!$spotifyPlaylistId || !$spotifySongId) {
            throw new \RuntimeException('Spotify playlist or song ID not found');
        }

        try {
            $this->client->post("playlists/{$spotifyPlaylistId}/tracks", [
                'headers' => ['Authorization' => 'Bearer ' . $accessToken],
                'json' => [
                    'uris' => ["spotify:track:{$spotifySongId}"],
                ],
            ]);

            return $song; // Return the song object after adding it to the playlist
        } catch (GuzzleException $e) {
            throw new \RuntimeException('Error while adding song to Spotify playlist: ' . $e->getMessage());
        }
    }
}
