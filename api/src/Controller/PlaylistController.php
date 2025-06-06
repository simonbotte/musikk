<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\PlaylistRepository;
use App\Repository\UserRepository;
use App\Service\AppleMusicService;
use App\Service\PlaylistDataService;
use App\Service\PlaylistService;
use App\Service\SongService;
use App\Service\SpotifyService;
use PlaylistDataName;
use PouleR\AppleMusicAPI\APIClient;
use PouleR\AppleMusicAPI\AppleMusicAPI;
use PouleR\AppleMusicAPI\Entity\LibraryResource;
use ServiceName;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/playlist', name: 'app_playlist')]
final class PlaylistController extends AbstractController
{
    public function __construct(
        #[Autowire('%appleMusicPrivateKey%')] private string $privateKey,
        #[Autowire('%appleMusicKeyId%')] private string $keyId,
        #[Autowire('%appleMusicTeamId%')] private string $teamId,
        private PlaylistRepository $playlistRepository,
        private UserRepository $userRepository,
        private PlaylistService $playlistService,
        private AppleMusicService $appleMusicService,
        private SpotifyService $spotifyService,
        private PlaylistDataService $playlistDataService,
        private SongService $songService,
    ) {
    }

    #[Route('/{userUuid}', name: '_all_of_user', methods: ['GET'])]
    public function playlists(string $userUuid, Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user || ($user instanceof User && $user->getUuid() !== $userUuid)) {
            return new JsonResponse(['error' => 'User not found'], 403);
        }

        $appleMusicPlaylists = $this->appleMusicService->getPlaylists($user);
        $spotifyPlaylists = $this->spotifyService->getPlaylists($user);
        
        $appleMusicPlaylistsFormated = $this->playlistService->formatPlaylists($appleMusicPlaylists->toArray());
        $spotifyPlaylistsFormated = $this->playlistService->formatPlaylists($spotifyPlaylists->toArray());
        $playslists = [
            'appleMusic' => $appleMusicPlaylistsFormated,
            'spotify' => $spotifyPlaylistsFormated,
        ];
        $response = new JsonResponse($playslists);
        return $response;
    }

    #[Route('/{userUuid}/{playlistUuid}', name: '_one', methods: ['GET'])]
    public function playlist(string $userUuid, string $playlistUuid, Request $request): JsonResponse
    {
        $user = $this->userRepository->findOneBy(['uuid' => $userUuid]);
        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], 403);
        }

        $playlist = $this->playlistRepository->findOneBy(['uuid' => $playlistUuid, 'user' => $user]);
        if (!$playlist) {
            return new JsonResponse(['error' => 'Playlist not found'], 403);
        }
        $playlistService = $this->playlistDataService->getData($playlist, PlaylistDataName::SERVICE_NAME);
        
        $formatedSongs = [];
        $songs = null;
        switch ($playlistService) {
            case ServiceName::APPLE_MUSIC:
                $songs = $this->appleMusicService->getPlaylistSongs($user, $playlist);
                break;
            case ServiceName::SPOTIFY:
                $songs = $this->spotifyService->getPlaylistSongs($user, $playlist);
                break;
            default:
                return new JsonResponse(['error' => 'Unknown service'], 400);
                break;
        }
        if ($songs) {
            $formatedSongs = $this->songService->formatSongs($songs->toArray());
        }
        $playlist = $playlist->toArray();
        $playlist['songs'] = $formatedSongs;
        $response = new JsonResponse($playlist);
        return $response;
    }

    #[Route('/{userUuid}/{playlistUuid}/add-song/{songId}', name: '_all', methods: ['POST'])]
    public function playlistAddSong(string $userUuid, string $playlistUuid, string $songId, Request $request): JsonResponse
    {
        $user = $this->userRepository->findOneBy(['uuid' => $userUuid]);
        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], 403);
        }

        $playlist = $this->playlistRepository->findOneBy(['uuid' => $playlistUuid, 'user' => $user]);
        if (!$playlist) {
            return new JsonResponse(['error' => 'Playlist not found'], 403);
        }

        $appleMusicUserToken = $this->appleMusicService->getUserToken($user);
        if (!$appleMusicUserToken) {
            return new JsonResponse(['error' => 'Apple Music user token not found'], 403);
        }

        $curl = new \Symfony\Component\HttpClient\CurlHttpClient();
        $client = new APIClient($curl);
        $client->setDeveloperToken($this->appleMusicService->generateToken());
        $client->setMusicUserToken($appleMusicUserToken);
        $api = new AppleMusicAPI($client);

        $song = new LibraryResource(
            id: $songId,
            type: 'songs',
        );

        $api->addTracksToLibraryPlaylist($this->playlistService->getPlaylistId($playlist), [$song]);

        $playlist = $this->playlistService->formatPlaylist($api, $playlist);

        $response = new JsonResponse($playlist);
        return $response;
    }

    #[Route('/{userUuid}/{playlistUuid}/remove-song/{songId}', name: '_all', methods: ['POST'])]
    public function playlistRemoveSong(string $userUuid, string $playlistUuid, string $songId, Request $request): JsonResponse
    {
        $user = $this->userRepository->findOneBy(['uuid' => $userUuid]);
        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], 403);
        }

        $playlist = $this->playlistRepository->findOneBy(['uuid' => $playlistUuid, 'user' => $user]);
        if (!$playlist) {
            return new JsonResponse(['error' => 'Playlist not found'], 403);
        }

        $appleMusicUserToken = $this->appleMusicService->getUserToken($user);
        if (!$appleMusicUserToken) {
            return new JsonResponse(['error' => 'Apple Music user token not found'], 403);
        }

        $curl = new \Symfony\Component\HttpClient\CurlHttpClient();
        $client = new APIClient($curl);
        $client->setDeveloperToken($this->appleMusicService->generateToken());
        $client->setMusicUserToken($appleMusicUserToken);
        $api = new AppleMusicAPI($client);

        $song = new LibraryResource(
            id: $songId,
            type: 'songs',
        );

        $api->addTracksToLibraryPlaylist($this->playlistService->getPlaylistId($playlist), [$song]);

        $playlist = $this->playlistService->formatPlaylist($api, $playlist);

        $response = new JsonResponse($playlist);
        return $response;
    }
}
