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

    #[Route('/api/plan/days/{id}', name: 'api_plan_day_update', methods: ['PATCH'])]
    public function updateDay(int $id, Request $request, PlanDayRepository $repo): JsonResponse
    {
        $day = $repo->find($id);
        if (!$day || $day->getPlan()->getUser() !== $this->getUser()) {
            return $this->json(['error' => 'Not found'], 404);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        if (isset($data['label'])) $day->setLabel($data['label']);
        if (isset($data['focus'])) $day->setFocus($data['focus']);
        if (isset($data['color'])) $day->setColor($data['color']);
        if (isset($data['note']))  $day->setNote($data['note'] ?: null);

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
        if (empty($data['name'])) {
            return $this->json(['error' => 'Name erforderlich'], 422);
        }

        $maxSort = 0;
        foreach ($day->getExercises() as $e) {
            $maxSort = max($maxSort, $e->getSortOrder());
        }

        $ex = new PlanExercise();
        $ex->setName(trim($data['name']));
        $ex->setSection($data['section'] ?? 'Hauptteil');
        $ex->setDefaultSets((int) ($data['defaultSets'] ?? 3));
        $ex->setDefaultReps(isset($data['defaultReps']) && $data['defaultReps'] !== '' ? (int) $data['defaultReps'] : null);
        $ex->setProgressionNote($data['progressionNote'] ?? null ?: null);
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
        if (isset($data['name']))            $ex->setName(trim($data['name']));
        if (isset($data['section']))         $ex->setSection($data['section']);
        if (isset($data['defaultSets']))     $ex->setDefaultSets((int) $data['defaultSets']);
        if (array_key_exists('defaultReps', $data))
            $ex->setDefaultReps($data['defaultReps'] !== '' && $data['defaultReps'] !== null ? (int) $data['defaultReps'] : null);
        if (array_key_exists('progressionNote', $data))
            $ex->setProgressionNote($data['progressionNote'] ?: null);
        if (isset($data['isNew']))           $ex->setIsNew((bool) $data['isNew']);

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

        $idx = array_search($ex, $siblings, true);
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
}
