<?php

namespace App\Controller;

use App\Entity\ApiToken;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

class ApiAuthController extends AbstractController
{
    #[Route('/api/auth/token', name: 'api_auth_token', methods: ['POST'])]
    public function token(
        Request                     $request,
        UserRepository              $users,
        UserPasswordHasherInterface $hasher,
        EntityManagerInterface      $em,
        RateLimiterFactory          $apiLoginLimiter
    ): JsonResponse {
        // 5 attempts per IP per 15 minutes
        $limiter = $apiLoginLimiter->create($request->getClientIp());
        if (!$limiter->consume(1)->isAccepted()) {
            return $this->json(['error' => 'Too many login attempts. Try again later.'], 429);
        }

        $data     = json_decode($request->getContent(), true) ?? [];
        $username = mb_substr(trim($data['username'] ?? ''), 0, 80);
        $password = $data['password'] ?? '';

        $user = $users->findOneBy(['username' => $username]);

        // Always run isPasswordValid even on unknown user to prevent timing attacks
        $dummyValid = false;
        if (!$user) {
            $hasher->hashPassword(new \App\Entity\User(), 'dummy');
        } else {
            $dummyValid = $hasher->isPasswordValid($user, $password);
        }

        if (!$user || !$dummyValid) {
            return $this->json(['error' => 'Invalid credentials'], 401);
        }

        $token = new ApiToken($user);
        $em->persist($token);
        $em->flush();

        return $this->json(['token' => $token->getToken(), 'username' => $user->getUsername()]);
    }
}
