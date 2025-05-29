<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/user', name: 'app_user', methods: ['GET'])]
final class UserController extends AbstractController
{
    private UserRepository $userRepository;
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    )
    {
        $this->userRepository = $userRepository;
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
    }

    #[Route('/informations', name: '_informations', methods: ['GET'])]
    public function user(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'User not found'], Response::HTTP_UNAUTHORIZED);
        }
        return $this->json([
            'email'  => $user->getEmail(),
            'uuid' => $user->getUuid(),
            'token' => "replace it with the token",
        ]);
    }

    // #[Route('/connections', name: '_connections', methods: ['GET'])]
    // public function connections(): JsonResponse
    // {
    //     $user = $this->getUser();
    //     if (!$user instanceof User) {
    //         return $this->json(['error' => 'User not found'], Response::HTTP_UNAUTHORIZED);
    //     }
    //     $connections = [];
    //     $appleMusicUserToken = $user->getAppleMusicUserToken($user);
    //     if ($appleMusicUserToken) {
    //         $connections["appleMusic"] = true;
    //     };

    //     $spotifyUserToken = $user->getSpotifyUserToken($user);
    //     return $this->json([
    //         'email'  => $user->getEmail(),
    //         'uuid' => $user->getUuid(),
    //         'token' => "replace it with the token",
    //     ]);
    // }
}
