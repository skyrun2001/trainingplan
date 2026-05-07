<?php

namespace App\Controller;

use App\Entity\PlanDay;
use App\Entity\PlanExercise;
use App\Repository\PlanDayRepository;
use App\Repository\PlanExerciseRepository;
use App\Service\DefaultPlanSeeder;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PlanController extends AbstractController
{
    // Input length caps to prevent database bloat / DoS via giant strings
    private const MAX_LABEL  = 100;
    private const MAX_NAME   = 150;
    private const MAX_FOCUS  = 200;
    private const MAX_NOTE   = 500;
    private const MAX_SECT   = 100;
    private const MAX_PROG   = 300;

    public function __construct(
        private readonly DefaultPlanSeeder      $seeder,
        private readonly EntityManagerInterface $em,
    ) {}

    #[Route('/', name: 'app_plan')]
    public function index(): Response
    {
        $plan = $this->seeder->seedIfNeeded($this->getUser());
        return $this->render('plan/index.html.twig', ['plan' => $plan]);
    }

    #[Route('/plans/edit', name: 'app_plan_edit')]
    public function edit(): Response
    {
        $plan = $this->seeder->seedIfNeeded($this->getUser());
        return $this->render('plan/edit.html.twig', ['plan' => $plan]);
    }

    // ── API: Day ────────────────────────────────────────────────────────────

    #[Route('/api/plan/days', name: 'api_plan_day_add', methods: ['POST'])]
    public function addDay(Request $request): JsonResponse
    {
        $plan = $this->seeder->seedIfNeeded($this->getUser());
        $data = json_decode($request->getContent(), true) ?? [];

        $label = mb_substr(trim($data['label'] ?? ''), 0, self::MAX_LABEL);
        if ($label === '') {
            return $this->json(['error' => 'Bezeichnung erforderlich'], 422);
        }

        $type = preg_replace('/[^a-z0-9_]/', '', strtolower($data['type'] ?? 'custom'));
        if ($type === '') $type = 'custom';

        $color = $this->sanitizeColor($data['color'] ?? null);

        $maxSort = 0;
        foreach ($plan->getDays() as $d) {
            $maxSort = max($maxSort, $d->getSortOrder());
        }

        $day = new PlanDay();
        $day->setType($type);
        $day->setLabel($label);
        $day->setColor($color);
        $day->setFocus(mb_substr(trim($data['focus'] ?? ''), 0, self::MAX_FOCUS) ?: null);
        $day->setSortOrder($maxSort + 1);
        $plan->addDay($day);

        $this->em->persist($day);
        $this->em->flush();

        return $this->json(['id' => $day->getId(), 'type' => $day->getType()], 201);
    }

    #[Route('/api/plan/days/{id}', name: 'api_plan_day_delete', methods: ['DELETE'])]
    public function deleteDay(int $id, PlanDayRepository $repo): JsonResponse
    {
        $day = $repo->find($id);
        if (!$day || $day->getPlan()->getUser() !== $this->getUser()) {
            return $this->json(['error' => 'Not found'], 404);
        }
        if ($day->getPlan()->getDays()->count() <= 1) {
            return $this->json(['error' => 'Mindestens ein Tag muss verbleiben'], 422);
        }

        $this->em->remove($day);
        $this->em->flush();

        return $this->json(['success' => true]);
    }

    #[Route('/api/plan/days/{id}', name: 'api_plan_day_update', methods: ['PATCH'])]
    public function updateDay(int $id, Request $request, PlanDayRepository $repo): JsonResponse
    {
        $day = $repo->find($id);
        if (!$day || $day->getPlan()->getUser() !== $this->getUser()) {
            return $this->json(['error' => 'Not found'], 404);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        if (isset($data['label'])) $day->setLabel(mb_substr(trim($data['label']), 0, self::MAX_LABEL));
        if (isset($data['focus'])) $day->setFocus(mb_substr(trim($data['focus']), 0, self::MAX_FOCUS) ?: null);
        if (isset($data['color'])) $day->setColor($this->sanitizeColor($data['color']));
        if (isset($data['note']))  $day->setNote(mb_substr(trim($data['note']), 0, self::MAX_NOTE) ?: null);

        $this->em->flush();
        return $this->json(['success' => true]);
    }

    // ── API: Exercise ────────────────────────────────────────────────────────

    #[Route('/api/plan/days/{dayId}/exercises', name: 'api_plan_exercise_add', methods: ['POST'])]
    public function addExercise(int $dayId, Request $request, PlanDayRepository $dayRepo): JsonResponse
    {
        $day = $dayRepo->find($dayId);
        if (!$day || $day->getPlan()->getUser() !== $this->getUser()) {
            return $this->json(['error' => 'Not found'], 404);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $name = mb_substr(trim($data['name'] ?? ''), 0, self::MAX_NAME);
        if ($name === '') {
            return $this->json(['error' => 'Name erforderlich'], 422);
        }

        $maxSort = 0;
        foreach ($day->getExercises() as $e) {
            $maxSort = max($maxSort, $e->getSortOrder());
        }

        $ex = new PlanExercise();
        $ex->setName($name);
        $ex->setSection(mb_substr(trim($data['section'] ?? 'Hauptteil'), 0, self::MAX_SECT));
        $ex->setDefaultSets(max(1, min(20, (int) ($data['defaultSets'] ?? 3))));
        $ex->setDefaultReps(isset($data['defaultReps']) && $data['defaultReps'] !== ''
            ? max(1, min(999, (int) $data['defaultReps'])) : null);
        $ex->setProgressionNote(mb_substr(trim($data['progressionNote'] ?? ''), 0, self::MAX_PROG) ?: null);
        $ex->setIsNew(!empty($data['isNew']));
        $ex->setSortOrder($maxSort + 1);
        $day->addExercise($ex);

        $this->em->persist($ex);
        $this->em->flush();

        return $this->json($ex->toArray(), 201);
    }

    #[Route('/api/plan/exercises/{id}', name: 'api_plan_exercise_update', methods: ['PATCH'])]
    public function updateExercise(int $id, Request $request, PlanExerciseRepository $repo): JsonResponse
    {
        $ex = $repo->find($id);
        if (!$ex || $ex->getDay()->getPlan()->getUser() !== $this->getUser()) {
            return $this->json(['error' => 'Not found'], 404);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        if (isset($data['name']))        $ex->setName(mb_substr(trim($data['name']), 0, self::MAX_NAME));
        if (isset($data['section']))     $ex->setSection(mb_substr(trim($data['section']), 0, self::MAX_SECT));
        if (isset($data['defaultSets'])) $ex->setDefaultSets(max(1, min(20, (int) $data['defaultSets'])));
        if (array_key_exists('defaultReps', $data))
            $ex->setDefaultReps($data['defaultReps'] !== '' && $data['defaultReps'] !== null
                ? max(1, min(999, (int) $data['defaultReps'])) : null);
        if (array_key_exists('progressionNote', $data))
            $ex->setProgressionNote(mb_substr(trim($data['progressionNote'] ?? ''), 0, self::MAX_PROG) ?: null);
        if (isset($data['isNew']))       $ex->setIsNew((bool) $data['isNew']);

        $this->em->flush();
        return $this->json($ex->toArray());
    }

    #[Route('/api/plan/exercises/{id}', name: 'api_plan_exercise_delete', methods: ['DELETE'])]
    public function deleteExercise(int $id, PlanExerciseRepository $repo): JsonResponse
    {
        $ex = $repo->find($id);
        if (!$ex || $ex->getDay()->getPlan()->getUser() !== $this->getUser()) {
            return $this->json(['error' => 'Not found'], 404);
        }

        $this->em->remove($ex);
        $this->em->flush();
        return $this->json(['success' => true]);
    }

    #[Route('/api/plan/exercises/{id}/move', name: 'api_plan_exercise_move', methods: ['POST'])]
    public function moveExercise(int $id, Request $request, PlanExerciseRepository $repo): JsonResponse
    {
        $ex = $repo->find($id);
        if (!$ex || $ex->getDay()->getPlan()->getUser() !== $this->getUser()) {
            return $this->json(['error' => 'Not found'], 404);
        }

        $direction = json_decode($request->getContent(), true)['direction'] ?? 'up';
        $siblings  = $ex->getDay()->getExercises()->toArray();
        usort($siblings, fn($a, $b) => $a->getSortOrder() <=> $b->getSortOrder());

        $idx     = array_search($ex, $siblings, true);
        $swapIdx = $direction === 'up' ? $idx - 1 : $idx + 1;

        if ($swapIdx >= 0 && $swapIdx < count($siblings)) {
            $swap = $siblings[$swapIdx];
            $tmp  = $ex->getSortOrder();
            $ex->setSortOrder($swap->getSortOrder());
            $swap->setSortOrder($tmp);
        }

        $this->em->flush();
        return $this->json(['success' => true]);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function sanitizeColor(?string $raw): string
    {
        $raw = trim($raw ?? '');
        return preg_match('/^#[0-9A-Fa-f]{6}$/', $raw) ? strtoupper($raw) : '#888888';
    }
}
