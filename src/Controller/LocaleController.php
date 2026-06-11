<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class LocaleController extends AbstractController
{
    private const ALLOWED = ['de', 'en'];

    #[Route('/locale/{locale}', name: 'app_locale', requirements: ['locale' => 'de|en'])]
    public function switch(string $locale, Request $request, EntityManagerInterface $em): Response
    {
        $request->getSession()->set('_locale', $locale);

        /** @var User|null $user */
        $user = $this->getUser();
        if ($user instanceof User) {
            $settings           = $user->getSettings();
            $settings['locale'] = $locale;
            $user->setSettings($settings);
            $em->flush();
        }

        // Only follow same-host referers — a foreign Referer would be an open redirect
        $referer = $request->headers->get('Referer', '');
        $parts   = parse_url($referer);
        $sameHost = $parts !== false
            && in_array($parts['scheme'] ?? '', ['http', 'https'], true)
            && ($parts['host'] ?? '') === $request->getHost();

        return $this->redirect($sameHost ? $referer : $this->generateUrl('app_dashboard'));
    }
}
