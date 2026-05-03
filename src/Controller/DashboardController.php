<?php

namespace App\Controller;

use App\Repository\ExerciseLogRepository;
use App\Repository\WorkoutSessionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(
        WorkoutSessionRepository $sessionRepo,
        ExerciseLogRepository $exerciseRepo
    ): Response {
        $recentSessions = $sessionRepo->findRecent(30);
        $streak         = $sessionRepo->getCurrentStreak();
        $countByType    = $sessionRepo->countByType();
        $personalBests  = $exerciseRepo->findMaxWeightPerExercise();

        $typeMap = [];
        foreach ($countByType as $row) {
            $typeMap[$row['type']] = $row['cnt'];
        }

        $last4Weeks = $sessionRepo->findByDateRange(
            new \DateTime('-28 days'),
            new \DateTime()
        );

        $weeklyData = $this->buildWeeklyData($last4Weeks);

        return $this->render('dashboard/index.html.twig', [
            'sessions'      => $recentSessions,
            'streak'        => $streak,
            'typeMap'       => $typeMap,
            'totalSessions' => array_sum(array_column($countByType, 'cnt')),
            'personalBests' => $personalBests,
            'weeklyData'    => $weeklyData,
            'exerciseNames' => $exerciseRepo->findAllExerciseNames(),
        ]);
    }

    #[Route('/api/progression/{exerciseName}', name: 'api_progression')]
    public function progression(
        string $exerciseName,
        ExerciseLogRepository $repo
    ): JsonResponse {
        $data = $repo->findProgressionChartData(urldecode($exerciseName));
        return $this->json($data);
    }

    #[Route('/api/history', name: 'api_history')]
    public function history(
        Request $request,
        WorkoutSessionRepository $repo
    ): JsonResponse {
        $limit    = min((int) $request->query->get('limit', 10), 50);
        $sessions = $repo->findRecent($limit);

        $data = array_map(function ($s) {
            $logs = [];
            foreach ($s->getExerciseLogs() as $log) {
                $key = $log->getExerciseName();
                if (!isset($logs[$key])) {
                    $logs[$key] = [];
                }
                $logs[$key][] = [
                    'set'    => $log->getSetNumber(),
                    'reps'   => $log->getReps(),
                    'weight' => $log->getWeightKg(),
                    'rpe'    => $log->getRpe(),
                ];
            }

            return [
                'id'       => $s->getId(),
                'date'     => $s->getDate()->format('d.m.Y'),
                'type'     => $s->getType(),
                'label'    => $s->getTypeLabel(),
                'color'    => $s->getTypeColor(),
                'duration' => $s->getDurationMinutes(),
                'notes'    => $s->getNotes(),
                'exercises'=> $logs,
            ];
        }, $sessions);

        return $this->json($data);
    }

    private function buildWeeklyData(array $sessions): array
    {
        $weeks = [];
        for ($i = 3; $i >= 0; $i--) {
            $start = new \DateTime("-{$i} weeks monday this week");
            $end   = clone $start;
            $end->modify('+6 days');
            $label = 'KW '.$start->format('W');
            $weeks[$label] = ['label' => $label, 'count' => 0, 'types' => []];
        }

        foreach ($sessions as $s) {
            $kw = 'KW '.$s->getDate()->format('W');
            if (isset($weeks[$kw])) {
                $weeks[$kw]['count']++;
                $weeks[$kw]['types'][] = $s->getType();
            }
        }

        return array_values($weeks);
    }
}
