<?php

namespace App\Controller;

use App\Service\AppleMusicService;
use App\Service\UserDataService;
use App\Enum\UserDataName;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Firebase\JWT\JWT;
use PouleR\AppleMusicAPI\APIClient;
use PouleR\AppleMusicAPI\AppleMusicAPI;
use Symfony\Component\HttpFoundation\Request;


#[Route('/apple-music', name: 'app_apple_music')]
final class AppleMusicController extends AbstractController
{
    private AppleMusicService $appleMusicService;
    private UserDataService $userDataService;

    public function __construct(
        #[Autowire('%appleMusicPrivateKey%')] private string $privateKey,
        #[Autowire('%appleMusicKeyId%')] private string $keyId,
        #[Autowire('%appleMusicTeamId%')] private string $teamId,
        AppleMusicService $appleMusicService,
        UserDataService $userDataService
    ) {
        $this->appleMusicService = $appleMusicService;
        $this->userDataService = $userDataService;
    }

    #[Route('/token/{time?}', name: '_token', methods: ['GET'])]
    public function token(int $time = 360, Request $request): JsonResponse
    {
        return new JsonResponse(['jwt' => $this->appleMusicService->generateToken($time)]);
    }

    #[Route('/user-token/save', name: '_user_token_save', methods: ['POST'])]
    public function saveUserToken(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'User not authenticated'], 403);
        }

        $data = json_decode($request->getContent(), true);
        if (!isset($data['token'])) {
            return new JsonResponse(['error' => 'Token not provided'], 400);
        }

        $this->userDataService->saveData($user, UserDataName::APPLE_MUSIC_USER_TOKEN, $data['token']);
        return new JsonResponse(['success' => true]);
    }

    #[Route('/search/{query}', name: '_search', methods: ['GET'])]
    public function search(string $query, Request $request): JsonResponse
    {
        $curl = new \Symfony\Component\HttpClient\CurlHttpClient();
        $client = new APIClient($curl);
        $client->setDeveloperToken($this->appleMusicService->generateToken());
        $api = new AppleMusicAPI($client);

        $songsResponse = $api->searchCatalog('fr', $query, 'songs', 10);
        $songs = array_map(function ($song) {
            return [
                "id" => $song->id,
                "title" => $song->attributes->name,
                "artist" => $song->attributes->artistName,
            ];
        }, $songsResponse->results->songs->data);

        $response = new JsonResponse($songs);
        return $response;
    }
}
