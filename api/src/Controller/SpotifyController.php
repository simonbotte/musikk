<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\SpotifyService;
use App\Service\UserDataService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use UserDataName;

#[Route('/spotify', name: 'app_spotify')]
final class SpotifyController extends AbstractController
{

    private SpotifyService $spotifyService;
    private UserRepository $userRepository;
    private UserDataService $userDataService;

    public function __construct(
        #[Autowire('%spotifyClientId%')] private string $clientId,
        #[Autowire('%spotifyClientSecret%')] private string $clientSecret,
        SpotifyService $spotifyService,
        UserRepository $userRepository,
        UserDataService $userDataService,
    )
    {
        $this->spotifyService = $spotifyService;
        $this->userRepository = $userRepository;
        $this->userDataService = $userDataService;
    }

    #[Route('/login-url', name: '_login')]
    public function loginUrl(): JsonResponse
    {
        return $this->json([
            'url' => $this->spotifyService->getLoginUrl(),
        ]);
    }

    #[Route('/first-login-workflow/{code}', name: '_first_login_workflow', methods: ['POST'])]
    public function firstLoginWorkflow(string $code, Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'User not found'], 403);
        }

        $connexionData = [];
        try {
            $connexionData = $this->spotifyService->getSpotifyAccessToken($code, "https://musikk.localhost/login/spotify", false);
            if ($connexionData['access_token'] && $connexionData['refresh_token']) {
                $this->userDataService->saveData($user, UserDataName::SPOTIFY_USER_TOKEN, $connexionData['access_token']);
                $this->userDataService->saveData($user, UserDataName::SPOTIFY_REFRESH_TOKEN, $connexionData['refresh_token']);
                return $this->json([
                    'access_token' => $connexionData['access_token'],
                ]);
            }
        } catch (\Exception $e) {
            return $this->json(['error' => 'Invalid code'], 403);
        }
        return $this->json(['error' => 'Error'], 403);
    }

    #[Route('/token/{refreshToken}', name: '_token')]
    public function token(string $refreshToken): JsonResponse
    {
        $user = $this->userRepository->find(1);
        $token = $this->spotifyService->getSpotifyAccessToken($refreshToken, "https://musikk.localhost/login/spotify");
        if ($token) {
            $this->userDataService->saveData($user, UserDataName::SPOTIFY_USER_TOKEN, $token['access_token']);
            $this->userDataService->saveData($user, UserDataName::SPOTIFY_REFRESH_TOKEN, $token['refresh_token']);
            return $this->json($token);
        }
        return $this->json(['error' => 'Invalid code'], 403);
    }

    #[Route('/user-id/{userUuid}', name: '_userId')]
    public function userId(string $userUuid): JsonResponse
    {
        $user = $this->userRepository->findOneBy(['uuid' => $userUuid]);
        return $this->json($this->spotifyService->getUserId($user));
    }

    
}
