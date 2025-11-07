<?php

namespace App\EventListener;

use Symfony\Component\HttpKernel\Event\RequestEvent;

class DebugRefreshTokenListener
{
    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        // Ne dump que sur le endpoint refresh
        if ('/token/refresh' !== $request->getPathInfo()) {
            return;
        }

        // Récupération du contenu
        $rawContent    = $request->getContent();
        $parsedContent = $request->request->all();

        // Formatage en texte simple
        $output = [
            "===== Refresh Token Debug =====",
            "raw content: "   . $rawContent,
            "parsed body: "   . json_encode($parsedContent, JSON_UNESCAPED_UNICODE),
            "================================",
        ];
        // Affichage du résultat
        // echo implode("\n", $output);
        // exit; // pour interrompre et voir le résultat
    }
}
