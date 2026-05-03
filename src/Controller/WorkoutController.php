<?php

namespace App\Controller;

use App\Entity\ExerciseLog;
use App\Entity\WorkoutSession;
use App\Repository\WorkoutSessionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class WorkoutController extends AbstractController
{
    private const PLAN = [
        'push' => [
            ['name' => 'Crosstrainer', 'defaultSets' => 1, 'defaultReps' => null, 'section' => 'Aufwärmen'],
            ['name' => 'Langhantel Bankdrücken', 'defaultSets' => 4, 'defaultReps' => 7, 'section' => 'Hauptteil'],
            ['name' => 'Plate Loaded Schulterdrücken', 'defaultSets' => 4, 'defaultReps' => 9, 'section' => 'Hauptteil'],
            ['name' => 'Maschine Brustpresse', 'defaultSets' => 3, 'defaultReps' => 10, 'section' => 'Hauptteil'],
            ['name' => 'Kurzhantel Seitheben', 'defaultSets' => 4, 'defaultReps' => 13, 'section' => 'Hauptteil'],
            ['name' => 'Seilzug Trizepsdrücken', 'defaultSets' => 3, 'defaultReps' => 11, 'section' => 'Hauptteil'],
            ['name' => 'Seilzug Trizeps über Kopf', 'defaultSets' => 3, 'defaultReps' => 11, 'section' => 'Hauptteil'],
        ],
        'pull' => [
            ['name' => 'Crosstrainer', 'defaultSets' => 1, 'defaultReps' => null, 'section' => 'Aufwärmen'],
            ['name' => 'Klimmzug (oder Latzug breit)', 'defaultSets' => 4, 'defaultReps' => 8, 'section' => 'Hauptteil'],
            ['name' => 'Langhantel Rudern vorgebeugt', 'defaultSets' => 4, 'defaultReps' => 8, 'section' => 'Hauptteil'],
            ['name' => 'Latzug neutraler Griff', 'defaultSets' => 3, 'defaultReps' => 11, 'section' => 'Hauptteil'],
            ['name' => 'Maschine Rudern', 'defaultSets' => 3, 'defaultReps' => 11, 'section' => 'Hauptteil'],
            ['name' => 'Reverse Butterfly / Face Pulls', 'defaultSets' => 3, 'defaultReps' => 13, 'section' => 'Hauptteil'],
            ['name' => 'Langhantel Bizeps-Curls', 'defaultSets' => 3, 'defaultReps' => 9, 'section' => 'Hauptteil'],
            ['name' => 'Hammer Curls Kurzhantel', 'defaultSets' => 3, 'defaultReps' => 11, 'section' => 'Hauptteil'],
        ],
        'legs' => [
            ['name' => 'Crosstrainer', 'defaultSets' => 1, 'defaultReps' => null, 'section' => 'Aufwärmen'],
            ['name' => 'Hackenschmidt Kniebeuge', 'defaultSets' => 4, 'defaultReps' => 8, 'section' => 'Hauptteil'],
            ['name' => 'Rumänisches Kreuzheben (RDL)', 'defaultSets' => 4, 'defaultReps' => 9, 'section' => 'Hauptteil'],
            ['name' => 'Beinpresse 45°', 'defaultSets' => 3, 'defaultReps' => 11, 'section' => 'Hauptteil'],
            ['name' => 'Maschine Beinbeuger', 'defaultSets' => 3, 'defaultReps' => 12, 'section' => 'Hauptteil'],
            ['name' => 'Bulgarian Splits', 'defaultSets' => 3, 'defaultReps' => 10, 'section' => 'Hauptteil'],
            ['name' => 'Wadenpresse sitzend', 'defaultSets' => 4, 'defaultReps' => 15, 'section' => 'Hauptteil'],
            ['name' => 'Adduktor / Abduktor Superset', 'defaultSets' => 2, 'defaultReps' => 15, 'section' => 'Hauptteil'],
        ],
        'upper' => [
            ['name' => 'Crosstrainer', 'defaultSets' => 1, 'defaultReps' => null, 'section' => 'Aufwärmen'],
            ['name' => 'Kurzhantel Schulterdrücken', 'defaultSets' => 4, 'defaultReps' => 9, 'section' => 'Schultern'],
            ['name' => 'Maschine Seitheben', 'defaultSets' => 4, 'defaultReps' => 13, 'section' => 'Schultern'],
            ['name' => 'Face Pulls Seilzug', 'defaultSets' => 3, 'defaultReps' => 15, 'section' => 'Schultern'],
            ['name' => 'Schrägbank Kurzhantel Bankdrücken', 'defaultSets' => 3, 'defaultReps' => 10, 'section' => 'Arme + Schrägbank'],
            ['name' => 'Klimmzug eng / Latzug eng', 'defaultSets' => 3, 'defaultReps' => 9, 'section' => 'Arme + Schrägbank'],
            ['name' => 'Bizeps + Trizeps Superset', 'defaultSets' => 3, 'defaultReps' => 11, 'section' => 'Arme + Schrägbank'],
        ],
    ];

    #[Route('/log', name: 'app_log')]
    public function index(Request $request): Response
    {
        $type = $request->query->get('type', 'push');
        if (!array_key_exists($type, self::PLAN)) {
            $type = 'push';
        }

        return $this->render('log/index.html.twig', [
            'type'      => $type,
            'exercises' => self::PLAN[$type],
            'plan'      => self::PLAN,
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
        if (!$session) {
            return $this->json(['error' => 'Nicht gefunden'], 404);
        }

        $em->remove($session);
        $em->flush();

        return $this->json(['success' => true]);
    }

    #[Route('/api/last-weights/{type}', name: 'api_last_weights')]
    public function lastWeights(string $type, WorkoutSessionRepository $repo): JsonResponse
    {
        $sessions = $repo->findByDateRange(new \DateTime('-60 days'), new \DateTime());
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
            if (count($weights) >= 10) break;
        }

        return $this->json($weights);
    }
}
