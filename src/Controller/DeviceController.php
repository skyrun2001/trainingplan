<?php

namespace App\Controller;

use App\Entity\DeviceToken;
use App\Repository\DeviceTokenRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class DeviceController extends AbstractController
{
    /**
     * Register (or refresh) an FCM token for the authenticated user.
     * If the token already exists for a different user (e.g. app re-install on
     * a new account) it is re-assigned to the current user.
     */
    #[Route('/api/device/token', name: 'api_device_token_register', methods: ['POST'])]
    public function register(
        Request                $request,
        DeviceTokenRepository  $repo,
        EntityManagerInterface $em
    ): JsonResponse {
        $data     = json_decode($request->getContent(), true) ?? [];
        $token    = trim((string) ($data['token'] ?? ''));
        $platform = in_array($data['platform'] ?? '', ['android', 'ios'], true)
            ? $data['platform'] : 'android';

        if ($token === '') {
            return $this->json(['error' => 'token is required'], 400);
        }

        $user     = $this->getUser();
        $existing = $repo->findOneByToken($token);

        if ($existing) {
            $existing->setUser($user)->setPlatform($platform)->touch();
        } else {
            $existing = (new DeviceToken())
                ->setUser($user)
                ->setToken($token)
                ->setPlatform($platform);
            $em->persist($existing);
        }

        $em->flush();

        return $this->json(['success' => true, 'id' => $existing->getId()]);
    }

    /**
     * Unregister an FCM token (call on logout or before token refresh).
     */
    #[Route('/api/device/token', name: 'api_device_token_unregister', methods: ['DELETE'])]
    public function unregister(
        Request                $request,
        DeviceTokenRepository  $repo,
        EntityManagerInterface $em
    ): JsonResponse {
        $data  = json_decode($request->getContent(), true) ?? [];
        $token = trim((string) ($data['token'] ?? ''));

        if ($token === '') {
            return $this->json(['error' => 'token is required'], 400);
        }

        $entity = $repo->findOneByToken($token);
        if (!$entity || $entity->getUser() !== $this->getUser()) {
            return $this->json(['error' => 'Not found'], 404);
        }

        $em->remove($entity);
        $em->flush();

        return $this->json(['success' => true]);
    }
}
