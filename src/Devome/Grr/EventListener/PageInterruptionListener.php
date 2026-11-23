<?php

namespace Devome\Grr\EventListener;

use Devome\Grr\Http\PageInterruption;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Transforme une PageInterruption (fin de page décidée par le code legacy) en réponse HTTP.
 * Priorité élevée : ce n'est pas une erreur, elle ne doit être ni journalisée ni affichée comme telle.
 */
#[AsEventListener(event: KernelEvents::EXCEPTION, priority: 4096)]
final class PageInterruptionListener
{
    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        if (!$exception instanceof PageInterruption) {
            return;
        }

        $response = $exception->toResponse();

        // En-têtes déjà relevés au démarrage de la page (GrrApplicationContext::boot())
        foreach ((array) ($GLOBALS['grr_bootstrap_headers'] ?? []) as $name => $values) {
            if (strcasecmp((string) $name, 'Location') !== 0) {
                $response->headers->set((string) $name, $values, false);
            }
        }

        // En-têtes posés depuis par le code legacy via header() (cookie de session, type de contenu…)
        foreach (headers_list() as $headerLine) {
            $separator = strpos($headerLine, ':');
            if ($separator === false) {
                continue;
            }
            $name = trim(substr($headerLine, 0, $separator));
            if (strcasecmp($name, 'Location') !== 0) {
                $response->headers->set($name, trim(substr($headerLine, $separator + 1)), false);
            }
        }
        $status = http_response_code();
        if (is_int($status) && $status >= 400 && $response->getStatusCode() === Response::HTTP_OK) {
            $response->setStatusCode($status);
        }
        header_remove();

        $event->allowCustomResponseCode();
        $event->setResponse($response);
        $event->stopPropagation();
    }
}
