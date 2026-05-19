<?php

namespace App\Service;

use App\Entity\HealthMetric;
use App\Entity\User;
use App\Repository\HealthMetricRepository;
use Doctrine\ORM\EntityManagerInterface;

class HealthMetricService
{
    public function __construct(
        private readonly HealthMetricRepository $repo,
        private readonly EntityManagerInterface $em,
    ) {}

    /**
     * Normalise an arbitrary string to a safe lowercase snake_case metric key.
     */
    public static function normaliseKey(string $raw): string
    {
        return substr(preg_replace('/[^a-z0-9_]/', '_', strtolower($raw)), 0, 100);
    }

    /**
     * Upsert an array of raw key→value pairs for a given user and date.
     * Keys are normalised; empty keys and non-numeric values are skipped.
     * Returns a map of saved key → float value.
     *
     * Does NOT flush — the caller controls the transaction boundary.
     */
    public function upsertMetrics(User $user, \DateTimeInterface $date, array $rawMetrics): array
    {
        $saved = [];
        foreach ($rawMetrics as $rawKey => $rawValue) {
            $key = self::normaliseKey((string) $rawKey);
            if ($key === '' || !is_numeric($rawValue)) {
                continue;
            }
            $value = (float) $rawValue;

            $record = $this->repo->findOneByUserDateKey($user, $date, $key)
                ?? (new HealthMetric())->setUser($user)->setDate($date)->setMetricKey($key);
            $record->setMetricValue($value);
            $this->em->persist($record);
            $saved[$key] = $value;
        }

        return $saved;
    }
}
