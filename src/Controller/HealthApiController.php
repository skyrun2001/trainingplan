<?php

namespace App\Controller;

use App\Repository\HealthMetricRepository;
use App\Repository\WorkoutSessionRepository;
use App\Service\HealthMetricService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class HealthApiController extends AbstractController
{
    /**
     * Upsert one or more health metrics for a given date.
     *
     * New format (recommended — works for any future Health Connect data type):
     *   POST /api/health/sync
     *   { "date": "2026-05-09", "metrics": { "steps": 8432, "weight_kg": 78.5, ... } }
     *
     * Legacy flat format still accepted (camelCase auto-mapped to snake_case):
     *   { "date": "...", "steps": 8432, "sleepMinutes": 427, "activeMinutes": 45,
     *     "weightKg": 78.5, "caloriesKcal": 2150 }
     */
    #[Route('/api/health/sync', name: 'api_health_sync', methods: ['POST'])]
    public function sync(
        Request             $request,
        HealthMetricService $healthMetric,
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

        // Prefer new "metrics" object; fall back to legacy flat camelCase keys
        if (isset($data['metrics']) && is_array($data['metrics'])) {
            $metrics = $data['metrics'];
        } else {
            $legacyMap = [
                'steps'         => 'steps',
                'sleepMinutes'  => 'sleep_minutes',
                'activeMinutes' => 'active_minutes',
                'weightKg'      => 'weight_kg',
                'caloriesKcal'  => 'calories_kcal',
            ];
            $metrics = [];
            foreach ($legacyMap as $camel => $snake) {
                if (array_key_exists($camel, $data) && $data[$camel] !== null) {
                    $metrics[$snake] = $data[$camel];
                }
            }
        }

        if (empty($metrics)) {
            return $this->json(['error' => 'No metrics provided'], 400);
        }

        $saved = array_keys($healthMetric->upsertMetrics($user, $date, $metrics));
        $em->flush();

        return $this->json(['success' => true, 'saved' => $saved]);
    }

    /**
     * Return health history grouped by date.
     *
     * GET /api/health/data?days=30
     * Response: [ { "date": "2026-05-09", "steps": 8432, "weight_kg": 78.5 }, ... ]
     */
    #[Route('/api/health/data', name: 'api_health_data', methods: ['GET'])]
    public function data(Request $request, HealthMetricRepository $repo): JsonResponse
    {
        $days = min((int) $request->query->get('days', 7), 90);
        return $this->json($repo->findRecentGroupedForUser($this->getUser(), $days));
    }

    /**
     * Return all distinct metric keys the user has ever synced.
     *
     * GET /api/health/metrics
     * Response: ["active_minutes", "calories_kcal", "steps", "weight_kg", ...]
     */
    #[Route('/api/health/metrics', name: 'api_health_metrics', methods: ['GET'])]
    public function metrics(HealthMetricRepository $repo): JsonResponse
    {
        return $this->json($repo->findAllMetricKeysForUser($this->getUser()));
    }

    /** Recent workout sessions for the Android dashboard */
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
