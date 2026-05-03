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

        $session = new WorkoutSession();
        $session->setType($data['type']);
        $session->setDate(new \DateTime($data['date'] ?? 'today'));
        $session->setDurationMinutes($data['duration'] ?? null);
        $session->setNotes($data['notes'] ?? null);
        $session->setUser($this->getUser());

        foreach ($data['exercises'] as $exerciseName => $sets) {
            foreach ($sets as $setData) {
                $log = new ExerciseLog();
                $log->setExerciseName($exerciseName);
                $log->setSetNumber($setData['set'] ?? 1);
                $log->setReps($setData['reps'] ?? null);
                $log->setWeightKg($setData['weight'] ?? null);
                $log->setRpe($setData['rpe'] ?? null);
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
