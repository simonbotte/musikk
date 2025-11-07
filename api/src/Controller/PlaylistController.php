<?php

namespace App\Controller;

use App\Entity\Collaboration;
use App\Entity\User;
use App\Repository\PlaylistRepository;
use App\Repository\UserRepository;
use App\Service\AppleMusicService;
use App\Service\PlaylistDataService;
use App\Service\PlaylistService;
use App\Service\SongService;
use App\Service\SpotifyService;
use Doctrine\ORM\EntityManagerInterface;
use App\Enum\PlaylistDataName;
use PouleR\AppleMusicAPI\APIClient;
use PouleR\AppleMusicAPI\AppleMusicAPI;
use PouleR\AppleMusicAPI\Entity\LibraryResource;
use App\Enum\ServiceName;
use App\Enum\SongDataName;
use App\Repository\SongRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

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
        private EntityManagerInterface $em,
        private SongRepository $songRepository,
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

    #[Route('/{playlistUuid}/collaborations', name: '_collaborations', methods: ['GET'])]
    public function collaboration(string $playlistUuid, Request $request): JsonResponse
    {
        $playlist = $this->playlistRepository->findOneBy(['uuid' => $playlistUuid]);
        if (!$playlist) {
            return new JsonResponse(['error' => 'Playlist not found'], 403);
        }

        $collaborations = $playlist->getCollaborations();
        $collaborationsArray = [];
        foreach ($collaborations as $collaboration) {
            $collaborationsArray[] = $collaboration->toArray();
        }
        $response = new JsonResponse($collaborationsArray);
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
        $songs = [];
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

    #[Route('/{userUuid}/{playlistUuid}/add-song/{songId}', name: '_add_song', methods: ['GET'])]
    public function addSongToPlaylist(string $userUuid, string $playlistUuid, string $songId, Request $request): JsonResponse
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
        $song = $this->songRepository->find($songId);
        if (!$song) {
            return new JsonResponse(['error' => 'Song not found'], 404);
        }

        if ($playlistService === ServiceName::APPLE_MUSIC) {
            $this->appleMusicService->addSongToPlaylist($user, $playlist, $song);
        }

        if ($playlistService === ServiceName::SPOTIFY) {
            $this->spotifyService->addSongToPlaylist($user, $playlist, $song);
        }

        return new JsonResponse(['message' => 'Song added to playlist successfully', "song" => $song->toArray()]);
    }

    #[Route('/{userUuid}/{playlistUuid}/add-collaboration-invitation', name: '_add_collaboration_invitation', methods: ['POST'])]
    public function addCollaboration(string $userUuid, string $playlistUuid, Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], 403);
        }

        $playlist = $this->playlistRepository->findOneBy(['uuid' => $playlistUuid, 'user' => $user]);
        if (!$playlist) {
            return new JsonResponse(['error' => 'Playlist not found'], 403);
        }
        if ($playlist->getUser()->getUuid() !== $userUuid) {
            return new JsonResponse(['error' => 'Error'], 403);
        }

        $data = json_decode($request->getContent(), true);
        $collaborationName = $data['name'] ?? null;
        $collaborationEmail = $data['email'] ?? null;
        $collaborationLimit = $data['addLimit'] ?? null;
        $collaboration = new Collaboration();
        $collaboration->setPlaylist($playlist);
        $collaboration->setName($collaborationName);
        $collaboration->setEmail($collaborationEmail);
        $collaboration->setAddLimit($collaborationLimit);
        $collaboration->setUuid(Uuid::v7());

        $this->em->persist($collaboration);
        $this->em->flush();
        // For now, we will just return a success message
        return new JsonResponse(['message' => 'Collaboration invitation added successfully']);
    }
}
