<?php

namespace App\Service;

use App\Entity\User;
use Firebase\JWT\JWT;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use UserDataName;

class AppleMusicService
{
    public function __construct(
        #[Autowire('%appleMusicPrivateKey%')] private string $privateKey,
        #[Autowire('%appleMusicKeyId%')] private string $keyId,
        #[Autowire('%appleMusicTeamId%')] private string $teamId,
    ) {
    }

    public function generateToken(int $time = 360): string
    {
        $token = [
            'iss' => $this->teamId,
            'iat' => time(),
            'exp' => time() + $time,
        ];

        $jwt = JWT::encode($token, $this->privateKey, 'ES256', $this->keyId);
        return $jwt;
    }

    public function getUserToken(User $user): string|null
    {
        $userData = $user->getUserData()->filter(function ($data) {
            return $data->getName() === UserDataName::APPLE_MUSIC_USER_TOKEN;
        })->first();

        if ($userData) {
            return $userData->getValue();
        }

        return null;
    }
}