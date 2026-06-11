<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * CSRF defence for the session-authenticated JSON endpoints.
 *
 * Browsers cannot attach a custom header to a cross-site request without a
 * CORS preflight, so requiring X-Requested-With on every mutating request
 * blocks CSRF even where no token is exchanged. The frontend sets the header
 * globally via the fetch wrapper in public/js/app.js.
 *
 * Exempt:
 *  - safe methods (GET/HEAD/OPTIONS)
 *  - the stateless Bearer-token API firewalls (no cookies → no CSRF surface)
 *  - /login and /logout, which use Symfony's own CSRF token handling
 */
class RequireXhrHeaderSubscriber implements EventSubscriberInterface
{
    private const EXEMPT_PATHS = '#^/(api/(auth|health|supplements|device)(/|$)|login$|logout$)#';

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => ['onKernelRequest', 9]];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if (in_array($request->getMethod(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            return;
        }
        if (preg_match(self::EXEMPT_PATHS, $request->getPathInfo())) {
            return;
        }

        if ($request->headers->get('X-Requested-With') !== 'XMLHttpRequest') {
            $event->setResponse(new JsonResponse(['error' => 'Missing X-Requested-With header'], 403));
        }
    }
}
