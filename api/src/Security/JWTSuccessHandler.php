<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Cookie;

class JWTSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    public function __construct(
        private JWTTokenManagerInterface $jwtManager
    ) {}

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): Response
    {
        $user = $token->getUser();

        $jwt = $this->jwtManager->create($user);

        $refreshCookie = Cookie::create('REFRESH_TOKEN')
            ->withValue($jwt)
            ->withHttpOnly(true)
            ->withSecure(true)
            ->withPath('/')
            ->withExpires(new \DateTime('+7 days'));

        $response = new JsonResponse(['token' => $jwt]);
        $response->headers->setCookie($refreshCookie);

        return $response;
    }
}
