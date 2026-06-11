<?php

namespace App\Security;

use App\Entity\ApiToken;
use App\Repository\ApiTokenRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class ApiTokenAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private readonly ApiTokenRepository     $repo,
        private readonly EntityManagerInterface $em,
    ) {}

    public function supports(Request $request): ?bool
    {
        return str_starts_with($request->headers->get('Authorization', ''), 'Bearer ');
    }

    public function authenticate(Request $request): Passport
    {
        $raw   = $request->headers->get('Authorization', '');
        $token = substr($raw, 7);

        return new SelfValidatingPassport(
            new UserBadge($token, function (string $token) {
                // Tokens are stored hashed; fall back to plaintext rows issued
                // before hashing and upgrade them in place.
                $apiToken = $this->repo->findOneBy(['token' => ApiToken::hashToken($token)]);
                if (!$apiToken) {
                    $apiToken = $this->repo->findOneBy(['token' => $token]);
                    if ($apiToken) {
                        $apiToken->rehash();
                        $this->em->flush();
                    }
                }
                if (!$apiToken || $apiToken->isExpired()) {
                    throw new CustomUserMessageAuthenticationException('Invalid or expired API token.');
                }
                return $apiToken->getUser();
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
    }
}
