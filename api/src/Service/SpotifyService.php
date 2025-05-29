<?php

namespace App\Service;

use App\Entity\Playlist;
use App\Entity\PlaylistData;
use App\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Dom\Entity;
use Firebase\JWT\JWT;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use PlaylistDataName;
use ServiceName;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;
use UserDataName;

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
    ) {
        $this->client = new Client([
            'base_uri' => 'https://api.spotify.com/v1/',
            'timeout' => 5.0,
        ]);
    }

    public function getLoginUrl(): string
    {
        $state = bin2hex(random_bytes(8));
        $scope = 'user-read-private user-read-email';
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
            // 1) Fetch & decode once
            $response = $this->client->get('me/playlists', [
                'headers' => ['Authorization' => 'Bearer ' . $accessToken],
            ]);
            $apiPlaylists = json_decode((string) $response->getBody(), true)['items'] ?? [];
            $apiPlaylistIds = array_column($apiPlaylists, 'name', 'id');

            $localPlaylists = $this->playlistService
                ->getUserPlaylistFromService($user, ServiceName::SPOTIFY);
            $localPlaylistWithApiIds = [];
            foreach ($localPlaylists as $playlist) {
                $playlistId = $this->playlistDataService->getData($playlist,PlaylistDataName::SPOTIFY_PLAYLIST_ID);
                $localPlaylistWithApiIds[$playlistId] = $playlist;
            }
            // 4) Figure out adds, deletes, updates
            $toAddIds    = array_diff_key($apiPlaylistIds, $localPlaylistWithApiIds);
            $toRemoveIds    = array_diff_key($localPlaylistWithApiIds, $apiPlaylistIds);
            $commonIds   = array_intersect_key($apiPlaylistIds, $localPlaylistWithApiIds);

            // 5) Add new playlists
            foreach ($toAddIds as $spotifyId => $name) {
                $pl = new Playlist();
                $pl->setUser($user)
                    ->setName($name)
                    ->setUuid(Uuid::v4());
                $this->em->persist($pl);

                $this->playlistDataService->saveData($pl, PlaylistDataName::SPOTIFY_PLAYLIST_ID, $spotifyId);
                $this->playlistDataService->saveData($pl, PlaylistDataName::SERVICE_NAME, ServiceName::SPOTIFY);
            }

            // 6) Delete removed playlists
            foreach (array_keys($toRemoveIds) as $spotifyId) {
                if ($pl = $this->playlistService
                    ->getPlaylist($user, $spotifyId, PlaylistDataName::SPOTIFY_PLAYLIST_ID)
                ) {
                    $this->em->remove($pl);
                }
            }

            // 7) Update renamed playlists
            foreach ($commonIds as $spotifyId => $newName) {
                $entity = $localPlaylistWithApiIds[$spotifyId];
                if ($entity->getName() !== $newName) {
                    $entity->setName($newName);
                    $this->em->persist($entity);
                }
            }

            // 8) Flush everything at once
            $this->em->flush();

            // 9) Return refreshed state
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

    public function formatPlaylist(Playlist $playlist) {}
}
