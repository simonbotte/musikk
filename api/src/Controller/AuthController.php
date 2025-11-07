<?php

namespace App\Controller;

use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use App\Repository\UserRepository;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthController
{
    #[Route('/token/refresh', name: 'refresh_token', methods: ['POST'])]
    public function refreshToken(
        Request $request,
        UserProviderInterface $userProvider,
        JWTTokenManagerInterface $JWTManager,
        UserRepository $userRepo
    ): JsonResponse {
        $refreshToken = $request->cookies->get('REFRESH_TOKEN');

        if (!$refreshToken) {
            return new JsonResponse(['error' => 'No refresh token'], 401);
        }

        try {
            $data = JWT::decode($refreshToken, new Key($_ENV['JWT_PUBLIC_KEY'], 'RS256'));
            $user = $userRepo->findOneBy(['email' => $data->username]);

            if (!$user) {
                throw new UserNotFoundException();
            }

            $newAccessToken = $JWTManager->create($user);
            return new JsonResponse([
                'token' => $newAccessToken,
            ]);

        } catch (\Throwable $e) {
            return new JsonResponse(['error' => 'Invalid refresh token'], 401);
        }
    }
}
