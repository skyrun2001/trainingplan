<?php

namespace App\EventSubscriber;

use App\Entity\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class LocaleSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly TokenStorageInterface $tokenStorage) {}

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        // Session locale (explicitly set by user via the switcher) wins over Accept-Language
        if ($locale = $request->getSession()->get('_locale')) {
            $request->setLocale($locale);
            return;
        }

        // Logged-in user with a saved locale preference
        $token = $this->tokenStorage->getToken();
        if ($token && ($user = $token->getUser()) instanceof User) {
            $settings = $user->getSettings();
            if (!empty($settings['locale'])) {
                $locale = $settings['locale'];
                $request->setLocale($locale);
                $request->getSession()->set('_locale', $locale);
            }
        }
        // No explicit preference → Symfony's set_locale_from_accept_language already ran (priority 32)
        // and set the locale from the browser header; we leave it as-is.
    }

    public static function getSubscribedEvents(): array
    {
        // Priority 4: after the security firewall (8) so the user token is available,
        // but before controllers (0).
        return [KernelEvents::REQUEST => [['onKernelRequest', 4]]];
    }
}
