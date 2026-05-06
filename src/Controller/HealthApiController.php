<?php

namespace App\Controller;

use App\Entity\HealthData;
use App\Repository\HealthDataRepository;
use App\Repository\WorkoutSessionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class HealthApiController extends AbstractController
{
    #[Route('/api/health/sync', name: 'api_health_sync', methods: ['POST'])]
    public function sync(
        Request                $request,
        HealthDataRepository   $repo,
        EntityManagerInterface $em
    ): JsonResponse {
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true) ?? [];

        $dateStr = $data['date'] ?? (new \DateTime())->format('Y-m-d');
        try {
            $date = new \DateTime($dateStr);
        } catch (\Exception) {
            return $this->json(['error' => 'Invalid date'], 400);
        }

        $record = $repo->findByUserAndDate($user, $date) ?? (new HealthData())->setUser($user)->setDate($date);

        if (array_key_exists('steps', $data))         $record->setSteps($data['steps'] !== null ? (int) $data['steps'] : null);
        if (array_key_exists('sleepMinutes', $data))   $record->setSleepMinutes($data['sleepMinutes'] !== null ? (int) $data['sleepMinutes'] : null);
        if (array_key_exists('activeMinutes', $data))  $record->setActiveMinutes($data['activeMinutes'] !== null ? (int) $data['activeMinutes'] : null);

        $em->persist($record);
        $em->flush();

        return $this->json(['success' => true]);
    }

    #[Route('/api/health/data', name: 'api_health_data', methods: ['GET'])]
    public function data(Request $request, HealthDataRepository $repo): JsonResponse
    {
        $days    = min((int) $request->query->get('days', 7), 90);
        $records = $repo->findRecentForUser($this->getUser(), $days);

        return $this->json(array_map(fn($r) => $r->toArray(), $records));
    }

    #[Route('/api/health/workouts', name: 'api_health_workouts', methods: ['GET'])]
    public function workouts(Request $request, WorkoutSessionRepository $repo): JsonResponse
    {
        $limit    = min((int) $request->query->get('limit', 5), 20);
        $sessions = $repo->findRecentForUser($this->getUser(), $limit);

        return $this->json(array_map(fn($s) => [
            'id'       => $s->getId(),
            'date'     => $s->getDate()->format('Y-m-d'),
            'type'     => $s->getType(),
            'label'    => $s->getTypeLabel(),
            'color'    => $s->getTypeColor(),
            'duration' => $s->getDurationMinutes(),
        ], $sessions));
    }
}
