<?php

namespace App\Controller;

use App\Entity\ApiToken;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class ApiAuthController extends AbstractController
{
    #[Route('/api/auth/token', name: 'api_auth_token', methods: ['POST'])]
    public function token(
        Request                     $request,
        UserRepository              $users,
        UserPasswordHasherInterface $hasher,
        EntityManagerInterface      $em
    ): JsonResponse {
        $data     = json_decode($request->getContent(), true) ?? [];
        $username = trim($data['username'] ?? '');
        $password = $data['password'] ?? '';

        $user = $users->findOneBy(['username' => $username]);
        if (!$user || !$hasher->isPasswordValid($user, $password)) {
            return $this->json(['error' => 'Invalid credentials'], 401);
        }

        $token = new ApiToken($user);
        $em->persist($token);
        $em->flush();

        return $this->json(['token' => $token->getToken(), 'username' => $user->getUsername()]);
    }
}
