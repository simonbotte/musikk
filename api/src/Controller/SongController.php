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
use App\Enum\PlaylistDataName;
use PouleR\AppleMusicAPI\APIClient;
use PouleR\AppleMusicAPI\AppleMusicAPI;
use PouleR\AppleMusicAPI\Entity\LibraryResource;
use App\Enum\ServiceName;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/song', name: 'app_song')]
final class SongController extends AbstractController
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

    #[Route('/search/{service}/{query}', name: '_search', methods: ['GET'])]
    #[IsGranted('PUBLIC_ACCESS')]
    public function search(string $query, string $service, Request $request): JsonResponse
    {
        $songs = [];
        $user = $this->getUser();
        switch ($service) {
            case ServiceName::APPLE_MUSIC:
                $songs = $this->appleMusicService->searchSong($query);
                break;
            case ServiceName::SPOTIFY:
                $songs = $this->spotifyService->searchSong($user, $query);
                break;
            default:
                return new JsonResponse(['error' => 'Unsupported service'], 400);
        }
        
        if (empty($songs)) {
            return new JsonResponse(['error' => 'No songs found'], 404);
        }
        return new JsonResponse($songs);
    }
}
