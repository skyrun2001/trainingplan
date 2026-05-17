<?php

namespace App\Controller;

use App\Entity\HealthMetric;
use App\Entity\User;
use App\Repository\ExerciseLogRepository;
use App\Repository\HealthMetricRepository;
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

    // Default display config when the user has no saved preference yet
    private const DEFAULT_HEALTH_DASHBOARD = [
        'cards'        => [],   // filled dynamically from available metric keys
        'chartMetrics' => [],   // filled dynamically
        'chartDays'    => 30,
    ];

    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(
        WorkoutSessionRepository $sessionRepo,
        ExerciseLogRepository    $exerciseRepo,
        HealthMetricRepository   $healthRepo
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

        // Health: generic key-value metrics
        $settings        = $user->getSettings();
        $availableKeys   = $healthRepo->findAllMetricKeysForUser($user);
        $healthDashboard = $this->resolveHealthDashboard(
            $settings['healthDashboard'] ?? null,
            $availableKeys
        );
        $chartDays       = $healthDashboard['chartDays'];
        $healthHistory   = $healthRepo->findRecentGroupedForUser($user, $chartDays);
        $todayMetrics    = $healthRepo->findTodayForUser($user);

        return $this->render('dashboard/index.html.twig', [
            'sessions'         => $recentSessions,
            'streak'           => $streak,
            'typeMap'          => $typeMap,
            'totalSessions'    => array_sum(array_column($countByType, 'cnt')),
            'personalBests'    => $personalBests,
            'weeklyData'       => $weeklyData,
            'exerciseNames'    => $exerciseRepo->findAllExerciseNamesForUser($user),
            'weekDays'         => $this->buildCurrentWeek($last4Weeks, $user),
            'schedule'         => $user->getWeekSchedule(),
            'reminderTime'     => $user->getReminderTime(),
            // Health
            'todayMetrics'     => $todayMetrics,
            'availableKeys'    => $availableKeys,
            'healthDashboard'  => $healthDashboard,
            'healthChartJson'  => json_encode($healthHistory, JSON_HEX_TAG | JSON_HEX_AMP | JSON_THROW_ON_ERROR),
        ]);
    }

    /** Manual health entry from the web (no app required) */
    #[Route('/health/save', name: 'health_save', methods: ['POST'])]
    public function saveHealth(
        Request                $request,
        HealthMetricRepository $repo,
        EntityManagerInterface $em
    ): JsonResponse {
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true) ?? [];

        $dateStr = $data['date'] ?? (new \DateTime())->format('Y-m-d');
        try {
            $date = new \DateTime($dateStr);
        } catch (\Exception) {
            return $this->json(['error' => 'Ungültiges Datum'], 400);
        }
        if ($date > new \DateTime('today')) {
            return $this->json(['error' => 'Kein Datum in der Zukunft'], 400);
        }

        $metrics = $data['metrics'] ?? [];
        if (!is_array($metrics) || empty($metrics)) {
            return $this->json(['error' => 'Keine Metriken angegeben'], 400);
        }

        $saved = [];
        foreach ($metrics as $rawKey => $rawValue) {
            $key = substr(preg_replace('/[^a-z0-9_]/', '_', strtolower((string) $rawKey)), 0, 100);
            if ($key === '' || !is_numeric($rawValue)) continue;

            $record = $repo->findOneByUserDateKey($user, $date, $key)
                ?? (new HealthMetric())->setUser($user)->setDate($date)->setMetricKey($key);
            $record->setMetricValue((float) $rawValue);
            $em->persist($record);
            $saved[$key] = (float) $rawValue;
        }
        $em->flush();

        return $this->json(['success' => true, 'saved' => $saved]);
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

        if (isset($data['healthDashboard']) && is_array($data['healthDashboard'])) {
            $hd = $data['healthDashboard'];
            $validKey = static fn($k) => (bool) preg_match('/^[a-z0-9_]{1,100}$/', (string) $k);

            $hdConfig = $current['healthDashboard'] ?? [];

            if (isset($hd['cards']) && is_array($hd['cards'])) {
                $hdConfig['cards'] = array_values(array_slice(array_filter(
                    array_map(fn($c) => isset($c['metric']) && $validKey($c['metric'])
                        ? ['metric' => $c['metric']] : null,
                        $hd['cards']
                    )
                ), 0, 20));
            }
            if (isset($hd['chartMetrics']) && is_array($hd['chartMetrics'])) {
                $hdConfig['chartMetrics'] = array_values(array_filter($hd['chartMetrics'], $validKey));
            }
            if (isset($hd['chartDays'])) {
                $hdConfig['chartDays'] = max(7, min(90, (int) $hd['chartDays']));
            }
            $current['healthDashboard'] = $hdConfig;
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

    /**
     * Resolves the effective health dashboard config.
     * If the user has no saved config, auto-generates defaults from available keys.
     */
    private function resolveHealthDashboard(?array $saved, array $availableKeys): array
    {
        // Known metrics with smart defaults (icon, color for display in PHP context is unused;
        // it's handled entirely in JS, but we need to know the preferred card/chart set)
        $preferredCards  = ['steps','sleep_minutes','active_minutes','weight_kg','calories_kcal',
                            'heart_rate_avg','heart_rate_resting','distance_meters','vo2_max'];
        $preferredCharts = ['steps','weight_kg','calories_kcal'];

        if ($saved !== null && isset($saved['cards'])) {
            return array_merge(self::DEFAULT_HEALTH_DASHBOARD, $saved);
        }

        // First visit: auto-select cards from available keys (up to 6), prefer known order
        $autoCards = [];
        foreach ($preferredCards as $k) {
            if (in_array($k, $availableKeys, true)) {
                $autoCards[] = ['metric' => $k];
            }
        }
        // Add any remaining unknown keys
        foreach ($availableKeys as $k) {
            if (!in_array($k, array_column($autoCards, 'metric'), true)) {
                $autoCards[] = ['metric' => $k];
            }
        }

        $autoCharts = array_values(array_intersect($preferredCharts, $availableKeys));

        return [
            'cards'        => array_slice($autoCards, 0, 6),
            'chartMetrics' => array_slice($autoCharts, 0, 3),
            'chartDays'    => 30,
        ];
    }

    private function buildCurrentWeek(array $last4Weeks, User $user): array
    {
        $today     = new \DateTime('today');
        $weekStart = (clone $today)->modify('monday this week');
        $weekEnd   = (clone $weekStart)->modify('+6 days');

        // Reuse already-fetched 28-day data instead of a separate DB query
        $weekSessions = array_filter(
            $last4Weeks,
            fn($s) => $s->getDate() >= $weekStart && $s->getDate() <= $weekEnd
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
