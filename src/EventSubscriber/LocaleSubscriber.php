<?php

namespace App\EventSubscriber;

use App\Entity\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\Translation\LocaleAwareInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class LocaleSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly TokenStorageInterface $tokenStorage,
        private readonly TranslatorInterface $translator,
    ) {}

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        // Session locale (explicitly set by user via the switcher) wins over Accept-Language
        if ($locale = $request->getSession()->get('_locale')) {
            $this->applyLocale($request, $locale);
            return;
        }

        // Logged-in user with a saved locale preference
        $token = $this->tokenStorage->getToken();
        if ($token && ($user = $token->getUser()) instanceof User) {
            $settings = $user->getSettings();
            if (!empty($settings['locale'])) {
                $locale = $settings['locale'];
                $request->getSession()->set('_locale', $locale);
                $this->applyLocale($request, $locale);
            }
        }
    }

    // LocaleAwareListener (priority 15) already pushed the locale into the translator
    // before this subscriber runs (priority 4). Calling setLocale on the translator
    // directly re-syncs it to the corrected locale.
    private function applyLocale(Request $request, string $locale): void
    {
        $request->setLocale($locale);
        if ($this->translator instanceof LocaleAwareInterface) {
            $this->translator->setLocale($locale);
        }
    }

    public static function getSubscribedEvents(): array
    {
        // After the security firewall (8) so the user token is available.
        return [KernelEvents::REQUEST => [['onKernelRequest', 4]]];
    }
}
