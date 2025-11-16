<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Handles CORS headers and OPTIONS preflight requests.
 */
class CorsSubscriber implements EventSubscriberInterface
{
    /**
     * @param string[] $allowedOrigins
     */
    public function __construct(private readonly array $allowedOrigins)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 250],
            KernelEvents::RESPONSE => ['onKernelResponse', -250],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if ($request->getMethod() !== 'OPTIONS') {
            return;
        }

        $headers = $this->buildHeaders($request);
        if ($headers === []) {
            return;
        }

        $event->setResponse(new Response('', Response::HTTP_NO_CONTENT, $headers));
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $headers = $this->buildHeaders($event->getRequest());
        if ($headers === []) {
            return;
        }

        foreach ($headers as $name => $value) {
            $event->getResponse()->headers->set($name, $value);
        }
    }

    /**
     * @return array<string, string>
     */
    private function buildHeaders(Request $request): array
    {
        $origin = $request->headers->get('Origin');
        if ($origin === null || !in_array($origin, $this->allowedOrigins, true)) {
            return [];
        }

        return [
            'Access-Control-Allow-Origin' => $origin,
            'Access-Control-Allow-Credentials' => 'true',
            'Access-Control-Allow-Methods' => 'GET, POST, PUT, PATCH, DELETE, OPTIONS',
            'Access-Control-Allow-Headers' => 'Authorization, Content-Type, X-Requested-With',
            'Access-Control-Max-Age' => '86400',
            'Vary' => 'Origin',
        ];
    }
}
