<?php

namespace App\Controller;

use App\Repository\ExerciseLogRepository;
use App\Repository\HealthDataRepository;
use App\Repository\WorkoutSessionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController
{
    private const DAY_LABELS = ['Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So'];

    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(
        WorkoutSessionRepository $sessionRepo,
        ExerciseLogRepository    $exerciseRepo,
        HealthDataRepository     $healthRepo
    ): Response {
        $user           = $this->getUser();
        $recentSessions = $sessionRepo->findRecentForUser($user, 30);
        $streak         = $sessionRepo->getCurrentStreakForUser($user);
        $countByType    = $sessionRepo->countByTypeForUser($user);
        $personalBests  = $exerciseRepo->findMaxWeightPerExerciseForUser($user);

        $typeMap = [];
        foreach ($countByType as $row) {
            $typeMap[$row['type']] = $row['cnt'];
        }

        $last4Weeks = $sessionRepo->findByDateRangeAndUser(
            new \DateTime('-28 days'), new \DateTime(), $user
        );
        $weeklyData = $this->buildWeeklyData($last4Weeks);

        $healthHistory   = $healthRepo->findRecentForUser($user, 7);
        $healthChartData = array_map(fn($h) => $h->toArray(), array_reverse($healthHistory));

        return $this->render('dashboard/index.html.twig', [
            'sessions'         => $recentSessions,
            'streak'           => $streak,
            'typeMap'          => $typeMap,
            'totalSessions'    => array_sum(array_column($countByType, 'cnt')),
            'personalBests'    => $personalBests,
            'weeklyData'       => $weeklyData,
            'exerciseNames'    => $exerciseRepo->findAllExerciseNamesForUser($user),
            'weekDays'         => $this->buildCurrentWeek($sessionRepo),
            'schedule'         => $user->getWeekSchedule(),
            'reminderTime'     => $user->getReminderTime(),
            'todayHealth'      => $healthRepo->findByUserAndDate($user, new \DateTime('today')),
            'healthHistory'    => $healthHistory,
            // Pre-encoded with JSON_HEX_TAG so </script> in any string field cannot escape the tag
            'healthChartJson'  => json_encode($healthChartData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_THROW_ON_ERROR),
        ]);
    }

    #[Route('/api/settings', name: 'api_settings', methods: ['PATCH'])]
    public function settings(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $user    = $this->getUser();
        $data    = json_decode($request->getContent(), true) ?? [];
        $current = $user->getSettings();

        if (isset($data['weekSchedule']) && is_array($data['weekSchedule'])) {
            $valid = ['push', 'pull', 'legs', 'upper'];
            $sched = [];
            foreach (range(1, 7) as $d) {
                $t = $data['weekSchedule'][(string) $d] ?? null;
                $sched[(string) $d] = in_array($t, $valid, true) ? $t : null;
            }
            $current['weekSchedule'] = $sched;
        }

        if (array_key_exists('reminderTime', $data)) {
            $t = $data['reminderTime'] ?? '';
            $current['reminderTime'] = preg_match('/^\d{2}:\d{2}$/', $t) ? $t : null;
        }

        $user->setSettings($current);
        $em->flush();

        return $this->json(['success' => true]);
    }

    #[Route('/api/progression/{exerciseName}', name: 'api_progression')]
    public function progression(string $exerciseName, ExerciseLogRepository $repo): JsonResponse
    {
        return $this->json($repo->findProgressionChartDataForUser(
            urldecode($exerciseName), $this->getUser()
        ));
    }

    #[Route('/api/history', name: 'api_history')]
    public function history(Request $request, WorkoutSessionRepository $repo): JsonResponse
    {
        $limit    = min((int) $request->query->get('limit', 10), 50);
        $sessions = $repo->findRecentForUser($this->getUser(), $limit);

        return $this->json(array_map(function ($s) {
            $logs = [];
            foreach ($s->getExerciseLogs() as $log) {
                $key = $log->getExerciseName();
                if (!isset($logs[$key])) $logs[$key] = [];
                $logs[$key][] = ['set' => $log->getSetNumber(), 'reps' => $log->getReps(),
                                 'weight' => $log->getWeightKg(), 'rpe' => $log->getRpe()];
            }
            return ['id' => $s->getId(), 'date' => $s->getDate()->format('d.m.Y'),
                    'type' => $s->getType(), 'label' => $s->getTypeLabel(),
                    'color' => $s->getTypeColor(), 'duration' => $s->getDurationMinutes(),
                    'notes' => $s->getNotes(), 'exercises' => $logs];
        }, $sessions));
    }

    // ── Private helpers ──────────────────────────────────────────────────────

    private function buildCurrentWeek(WorkoutSessionRepository $repo): array
    {
        $user      = $this->getUser();
        $today     = new \DateTime('today');
        $weekStart = (clone $today)->modify('monday this week');

        $weekSessions = $repo->findByDateRangeAndUser(
            $weekStart,
            (clone $weekStart)->modify('+6 days'),
            $user
        );

        $schedule = $user->getWeekSchedule();
        $days     = [];

        for ($i = 0; $i < 7; $i++) {
            $date        = (clone $weekStart)->modify("+{$i} days");
            $dateStr     = $date->format('Y-m-d');
            $isoWeekday  = (string) $date->format('N');
            $scheduledType = $schedule[$isoWeekday] ?? null;

            $daySessions = array_values(array_filter(
                $weekSessions,
                fn($s) => $s->getDate()->format('Y-m-d') === $dateStr
            ));

            $days[] = [
                'date'          => $dateStr,
                'label'         => self::DAY_LABELS[$i],
                'dayNum'        => $date->format('d'),
                'isoWeekday'    => $isoWeekday,
                'scheduledType' => $scheduledType,
                'hasSessions'   => count($daySessions) > 0,
                'sessionTypes'  => array_unique(array_map(fn($s) => $s->getType(), $daySessions)),
                'isToday'       => $dateStr === $today->format('Y-m-d'),
                'isPast'        => $date < $today,
            ];
        }

        return $days;
    }

    private function buildWeeklyData(array $sessions): array
    {
        $weeks = [];
        for ($i = 3; $i >= 0; $i--) {
            $start = new \DateTime("-{$i} weeks monday this week");
            $label = 'KW ' . $start->format('W');
            $weeks[$label] = ['label' => $label, 'count' => 0];
        }

        foreach ($sessions as $s) {
            $kw = 'KW ' . $s->getDate()->format('W');
            if (isset($weeks[$kw])) $weeks[$kw]['count']++;
        }

        return array_values($weeks);
    }
}
