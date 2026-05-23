<?php

namespace App\Controller;

use App\Entity\Supplement;
use App\Repository\SupplementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SupplementController extends AbstractController
{
    // ── Web routes (session auth) ─────────────────────────────────────────────

    #[Route('/supplements', name: 'app_supplements', methods: ['GET'])]
    public function index(SupplementRepository $repo): Response
    {
        $supplements = $repo->findAllForUser($this->getUser());

        return $this->render('supplement/index.html.twig', [
            'supplements'     => $supplements,
            'supplementsJson' => json_encode(
                array_map(fn($s) => $s->toArray(), $supplements),
                JSON_HEX_TAG | JSON_HEX_AMP | JSON_THROW_ON_ERROR
            ),
        ]);
    }

    // ── Web CRUD routes (session auth — called by the browser JS) ────────────

    #[Route('/supplements', name: 'web_supplements_create', methods: ['POST'])]
    public function webCreate(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        [$ok, $err] = $this->validatePayload($data, true);
        if (!$ok) { return $this->json(['error' => $err], 400); }

        $s = new Supplement();
        $s->setUser($this->getUser());
        $this->applyPayload($s, $data);
        $em->persist($s);
        $em->flush();

        return $this->json($s->toArray(), 201);
    }

    #[Route('/supplements/{id}', name: 'web_supplements_update', methods: ['PATCH'])]
    public function webUpdate(int $id, Request $request, SupplementRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        $s = $this->findOwned($id, $repo);
        if ($s === null) { return $this->json(['error' => 'Not found'], 404); }

        $data = json_decode($request->getContent(), true) ?? [];
        [$ok, $err] = $this->validatePayload($data, false);
        if (!$ok) { return $this->json(['error' => $err], 400); }

        $this->applyPayload($s, $data);
        $em->flush();

        return $this->json($s->toArray());
    }

    #[Route('/supplements/{id}', name: 'web_supplements_delete', methods: ['DELETE'])]
    public function webDelete(int $id, SupplementRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        $s = $this->findOwned($id, $repo);
        if ($s === null) { return $this->json(['error' => 'Not found'], 404); }

        $em->remove($s);
        $em->flush();

        return $this->json(['success' => true]);
    }

    #[Route('/supplements/{id}/dose', name: 'web_supplements_dose', methods: ['POST'])]
    public function webDose(int $id, Request $request, SupplementRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        $s = $this->findOwned($id, $repo);
        if ($s === null) { return $this->json(['error' => 'Not found'], 404); }

        $data   = json_decode($request->getContent(), true) ?? [];
        $amount = isset($data['amount']) ? max(0.0, (float) $data['amount']) : (float) $s->getServingsPerDay();
        $s->setServingsRemaining($s->getServingsRemaining() - $amount);
        $em->flush();

        return $this->json($s->toArray());
    }

    #[Route('/supplements/{id}/restock', name: 'web_supplements_restock', methods: ['POST'])]
    public function webRestock(int $id, Request $request, SupplementRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        $s = $this->findOwned($id, $repo);
        if ($s === null) { return $this->json(['error' => 'Not found'], 404); }

        $data = json_decode($request->getContent(), true) ?? [];
        if (!isset($data['servings']) || !is_numeric($data['servings']) || (float) $data['servings'] <= 0) {
            return $this->json(['error' => 'servings must be a positive number'], 400);
        }

        $s->setTotalServings((float) $data['servings']);
        $s->setServingsRemaining((float) $data['servings']);
        $em->flush();

        return $this->json($s->toArray());
    }

    // ── API routes (Bearer token auth — handled by api firewall) ─────────────

    /** List all supplements. */
    #[Route('/api/supplements', name: 'api_supplements_list', methods: ['GET'])]
    public function apiList(SupplementRepository $repo): JsonResponse
    {
        return $this->json(array_map(
            fn($s) => $s->toArray(),
            $repo->findAllForUser($this->getUser())
        ));
    }

    /** Create a supplement. */
    #[Route('/api/supplements', name: 'api_supplements_create', methods: ['POST'])]
    public function apiCreate(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        [$ok, $err] = $this->validatePayload($data, true);
        if (!$ok) {
            return $this->json(['error' => $err], 400);
        }

        $s = new Supplement();
        $s->setUser($this->getUser());
        $this->applyPayload($s, $data);
        $em->persist($s);
        $em->flush();

        return $this->json($s->toArray(), 201);
    }

    /** Update a supplement. */
    #[Route('/api/supplements/{id}', name: 'api_supplements_update', methods: ['PATCH'])]
    public function apiUpdate(int $id, Request $request, SupplementRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        $s = $this->findOwned($id, $repo);
        if ($s === null) {
            return $this->json(['error' => 'Not found'], 404);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        [$ok, $err] = $this->validatePayload($data, false);
        if (!$ok) {
            return $this->json(['error' => $err], 400);
        }

        $this->applyPayload($s, $data);
        $em->flush();

        return $this->json($s->toArray());
    }

    /** Log a dose (decrement servingsRemaining by servingsPerDay). */
    #[Route('/api/supplements/{id}/dose', name: 'api_supplements_dose', methods: ['POST'])]
    public function apiDose(int $id, Request $request, SupplementRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        $s = $this->findOwned($id, $repo);
        if ($s === null) {
            return $this->json(['error' => 'Not found'], 404);
        }

        $data   = json_decode($request->getContent(), true) ?? [];
        $amount = isset($data['amount']) ? max(0.0, (float) $data['amount']) : (float) $s->getServingsPerDay();

        $s->setServingsRemaining($s->getServingsRemaining() - $amount);
        $em->flush();

        return $this->json($s->toArray());
    }

    /** Restock: set new totalServings + servingsRemaining. */
    #[Route('/api/supplements/{id}/restock', name: 'api_supplements_restock', methods: ['POST'])]
    public function apiRestock(int $id, Request $request, SupplementRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        $s = $this->findOwned($id, $repo);
        if ($s === null) {
            return $this->json(['error' => 'Not found'], 404);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        if (!isset($data['servings']) || !is_numeric($data['servings']) || (float) $data['servings'] <= 0) {
            return $this->json(['error' => 'servings must be a positive number'], 400);
        }

        $servings = (float) $data['servings'];
        $s->setTotalServings($servings);
        $s->setServingsRemaining($servings);
        $em->flush();

        return $this->json($s->toArray());
    }

    /** Delete a supplement. */
    #[Route('/api/supplements/{id}', name: 'api_supplements_delete', methods: ['DELETE'])]
    public function apiDelete(int $id, SupplementRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        $s = $this->findOwned($id, $repo);
        if ($s === null) {
            return $this->json(['error' => 'Not found'], 404);
        }

        $em->remove($s);
        $em->flush();

        return $this->json(['success' => true]);
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function findOwned(int $id, SupplementRepository $repo): ?Supplement
    {
        $s = $repo->find($id);
        return ($s && $s->getUser() === $this->getUser()) ? $s : null;
    }

    /** Returns [bool $ok, ?string $error] */
    private function validatePayload(array $data, bool $requireName): array
    {
        if ($requireName && (empty($data['name']) || !is_string($data['name']))) {
            return [false, 'name is required'];
        }
        if (isset($data['servingsPerDay']) && ((int) $data['servingsPerDay']) < 1) {
            return [false, 'servingsPerDay must be >= 1'];
        }
        if (isset($data['warningDays']) && ((int) $data['warningDays']) < 1) {
            return [false, 'warningDays must be >= 1'];
        }
        return [true, null];
    }

    private function applyPayload(Supplement $s, array $data): void
    {
        if (isset($data['name'])) {
            $s->setName(mb_substr(trim((string) $data['name']), 0, 100));
        }
        if (array_key_exists('dosage', $data)) {
            $v = $data['dosage'];
            $s->setDosage($v !== null ? mb_substr(trim((string) $v), 0, 50) : null);
        }
        if (isset($data['servingsPerDay'])) {
            $s->setServingsPerDay(max(1, (int) $data['servingsPerDay']));
        }
        if (isset($data['servingsRemaining'])) {
            $s->setServingsRemaining(max(0.0, (float) $data['servingsRemaining']));
        }
        if (isset($data['totalServings'])) {
            $s->setTotalServings(max(0.0, (float) $data['totalServings']));
        }
        if (array_key_exists('unit', $data)) {
            $v = $data['unit'];
            $s->setUnit($v !== null ? mb_substr(trim((string) $v), 0, 30) : null);
        }
        if (isset($data['warningDays'])) {
            $s->setWarningDays(max(1, (int) $data['warningDays']));
        }
        if (array_key_exists('notes', $data)) {
            $v = $data['notes'];
            $s->setNotes($v !== null ? mb_substr(trim((string) $v), 0, 255) : null);
        }
        if (isset($data['sortOrder'])) {
            $s->setSortOrder((int) $data['sortOrder']);
        }
        if (array_key_exists('schedule', $data) && is_array($data['schedule'])) {
            $valid = ['morning', 'evening', 'pre_training', 'post_training'];
            $s->setSchedule(array_values(array_intersect($data['schedule'], $valid)));
        }
    }
}
