<?php

namespace App\Controller;

use App\Entity\ExerciseLog;
use App\Entity\WorkoutSession;
use App\Repository\WorkoutSessionRepository;
use App\Service\DefaultPlanSeeder;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class WorkoutController extends AbstractController
{
    public function __construct(
        private readonly DefaultPlanSeeder $seeder,
    ) {}

    #[Route('/log', name: 'app_log')]
    public function index(Request $request): Response
    {
        $type = $request->query->get('type', 'push');
        $plan = $this->seeder->seedIfNeeded($this->getUser());

        $day  = $plan->getDayByType($type);
        if (!$day) {
            // Fall back to first day if type doesn't exist
            $day  = $plan->getDays()->first() ?: null;
            $type = $day?->getType() ?? 'push';
        }

        return $this->render('log/index.html.twig', [
            'type'      => $type,
            'exercises' => $day ? $day->getExercisesAsArray() : [],
            'plan'      => $plan,
        ]);
    }

    #[Route('/log/save', name: 'app_log_save', methods: ['POST'])]
    public function save(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['type'], $data['exercises'])) {
            return $this->json(['error' => 'Ungültige Daten'], 400);
        }

        // Validate type to prevent arbitrary string injection
        $validTypes = ['push', 'pull', 'legs', 'upper', 'custom'];
        $type = in_array($data['type'], $validTypes, true) ? $data['type']
            : preg_replace('/[^a-z0-9_]/', '', strtolower($data['type']));

        $session = new WorkoutSession();
        $session->setType($type);
        try {
            $session->setDate(new \DateTime($data['date'] ?? 'today'));
        } catch (\Exception) {
            $session->setDate(new \DateTime('today'));
        }
        $session->setDurationMinutes(isset($data['duration']) ? max(1, min(600, (int) $data['duration'])) : null);
        $session->setNotes(mb_substr(trim($data['notes'] ?? ''), 0, 1000) ?: null);
        $session->setUser($this->getUser());

        $setCount = 0;
        foreach ($data['exercises'] as $exerciseName => $sets) {
            $exerciseName = mb_substr(trim((string) $exerciseName), 0, 150);
            foreach ((array) $sets as $setData) {
                if (++$setCount > 200) break 2; // cap to prevent DoS
                $log = new ExerciseLog();
                $log->setExerciseName($exerciseName);
                $log->setSetNumber(max(1, min(99, (int) ($setData['set'] ?? 1))));
                $log->setReps(isset($setData['reps']) ? max(1, min(9999, (int) $setData['reps'])) : null);
                $log->setWeightKg(isset($setData['weight']) ? max(0, min(999, (float) $setData['weight'])) : null);
                $log->setRpe(isset($setData['rpe']) ? max(1, min(10, (int) $setData['rpe'])) : null);
                $session->addExerciseLog($log);
            }
        }

        $em->persist($session);
        $em->flush();

        return $this->json(['id' => $session->getId(), 'success' => true]);
    }

    #[Route('/log/{id}/delete', name: 'app_log_delete', methods: ['DELETE'])]
    public function delete(
        int $id,
        WorkoutSessionRepository $repo,
        EntityManagerInterface $em
    ): JsonResponse {
        $session = $repo->find($id);
        if (!$session || $session->getUser() !== $this->getUser()) {
            return $this->json(['error' => 'Nicht gefunden'], 404);
        }

        $em->remove($session);
        $em->flush();

        return $this->json(['success' => true]);
    }

    #[Route('/api/last-weights/{type}', name: 'api_last_weights')]
    public function lastWeights(string $type, WorkoutSessionRepository $repo): JsonResponse
    {
        $sessions = $repo->findByDateRangeAndUser(
            new \DateTime('-60 days'),
            new \DateTime(),
            $this->getUser()
        );
        $sessions = array_filter($sessions, fn($s) => $s->getType() === $type);
        usort($sessions, fn($a, $b) => $b->getDate() <=> $a->getDate());

        $weights = [];
        foreach ($sessions as $session) {
            foreach ($session->getExerciseLogs() as $log) {
                $name = $log->getExerciseName();
                if (!isset($weights[$name]) && $log->getWeightKg() !== null) {
                    $weights[$name] = [
                        'weight' => $log->getWeightKg(),
                        'reps'   => $log->getReps(),
                        'date'   => $session->getDate()->format('d.m.Y'),
                    ];
                }
            }
        }

        return $this->json($weights);
    }
}
