<?php

namespace App\Controller;

use App\Entity\Playlist;
use App\Repository\CollaborationRepository;
use App\Repository\PlaylistRepository;
use App\Service\AppleMusicService;
use App\Service\PlaylistDataService;
use App\Service\PlaylistService;
use App\Service\SongService;
use App\Service\SpotifyService;
use App\Enum\ServiceName;
use Doctrine\ORM\EntityManagerInterface;
use App\Enum\PlaylistDataName;
use App\Repository\SongRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/collaboration', name: 'app_collaboration')]
final class CollaborationController extends AbstractController
{

    public function __construct(
        #[Autowire('%appleMusicPrivateKey%')] private string $privateKey,
        #[Autowire('%appleMusicKeyId%')] private string $keyId,
        #[Autowire('%appleMusicTeamId%')] private string $teamId,
        private CollaborationRepository $collaborationRepository,
        private PlaylistRepository $playlistRepository,
        private PlaylistService $playlistService,
        private PlaylistDataService $playlistDataService,
        private AppleMusicService $appleMusicService,
        private SpotifyService $spotifyService,
        private SongService $songService,
        private EntityManagerInterface $em,
        private SongRepository $songRepository,
    ) {
    }

    #[Route('/{collaborationUuid}', name: '_index', methods: ['GET'])]
    #[IsGranted('PUBLIC_ACCESS')]
    public function index(string $collaborationUuid, Request $request): JsonResponse
    {
        $collaboration = $this->collaborationRepository->findOneBy(['uuid' => $collaborationUuid]);
        if (!$collaboration) {
            return new JsonResponse(['error' => 'Collaboration invitation not found'], 403);
        }

        $response = new JsonResponse($collaboration->toArray());
        return $response;
    }

    #[Route('/{collaborationUuid}/edit', name: '_edit', methods: ['POST'])]
    public function edit(string $collaborationUuid, Request $request): JsonResponse
    {
        $collaboration = $this->collaborationRepository->findOneBy(['uuid' => $collaborationUuid]);
        if (!$collaboration) {
            return new JsonResponse(['error' => 'Collaboration invitation not found'], 403);
        }

        $data = json_decode($request->getContent(), true);
        $collaborationName = $data['name'] ?? null;
        $collaborationEmail = $data['email'] ?? null;
        $collaborationLimit = $data['addLimit'] ?? null;
        $collaboration->setName($collaborationName ?? $collaboration->getName());
        $collaboration->setEmail($collaborationEmail ?? $collaboration->getEmail());
        $collaboration->setAddLimit($collaborationLimit ?? $collaboration->getAddLimit());

        $this->em->persist($collaboration);
        $this->em->flush();

        $response = new JsonResponse($collaboration->toArray());
        return $response;
    }

    #[Route('/{collaborationUuid}/{playlistUuid}', name: '_playlist', methods: ['GET'])]
    #[IsGranted('PUBLIC_ACCESS')]
    public function playlist(string $collaborationUuid, string $playlistUuid, Request $request): JsonResponse
    {
        $collaboration = $this->collaborationRepository->findOneBy(['uuid' => $collaborationUuid]);
        if (!$collaboration) {
            return new JsonResponse(['error' => 'Collaboration invitation not found'], 403);
        }

        $playlist = $collaboration->getPlaylist();
        if (!$playlist || $playlist->getUuid() !== $playlistUuid) {
            return new JsonResponse(['error' => 'Playlist not found'], 403);
        }
        
        $formatedSongs = [];
        $songs = null;
        $playlistService = $this->playlistDataService->getData($playlist, PlaylistDataName::SERVICE_NAME);
        switch ($playlistService) {
            case ServiceName::APPLE_MUSIC:
                $songs = $this->appleMusicService->getPlaylistSongs($playlist->getUser(), $playlist);
                break;
            case ServiceName::SPOTIFY:
                $songs = $this->spotifyService->getPlaylistSongs($playlist->getUser(), $playlist);
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

    #[Route('/{collaborationUuid}/{playlistUuid}/add-song/{songId}', name: '_add_song', methods: ['GET'])]
    public function addSongToPlaylist(string $collaborationUuid, string $playlistUuid, string $songId, Request $request): JsonResponse
    {
        $collaboration = $this->collaborationRepository->findOneBy(['uuid' => $collaborationUuid]);
        if (!$collaboration) {
            return new JsonResponse(['error' => 'Collaboration invitation not found'], 403);
        }

        $playlist = $collaboration->getPlaylist();
        if (!$playlist || $playlist->getUuid() !== $playlistUuid) {
            return new JsonResponse(['error' => 'Playlist not found'], 403);
        }
        
        $user = $playlist->getUser();

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

        $collaboration->setAddedSongsCount($collaboration->getAddedSongsCount() + 1);
        $this->em->persist($collaboration);
        $this->em->flush();

        return new JsonResponse(['message' => 'Song added to playlist successfully', "song" => $song->toArray()]);
    }

    #[Route('/{collaborationUuid}/remove', name: '_remove', methods: ['DELETE'])]
    public function remove(string $collaborationUuid, Request $request): JsonResponse
    {
        $collaboration = $this->collaborationRepository->findOneBy(['uuid' => $collaborationUuid]);
        if (!$collaboration) {
            return new JsonResponse(['error' => 'Collaboration invitation not found'], 403);
        }

        $this->em->remove($collaboration);
        $this->em->flush();
        
        return new JsonResponse(['message' => 'Collaboration invitation removed successfully']);
    }
}
