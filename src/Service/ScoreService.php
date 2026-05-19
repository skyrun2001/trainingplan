<?php

namespace App\Service;

use App\Entity\WorkoutSession;

class ScoreService
{
    // Total volume (kg) for a full-score session: ~5 exercises × 4 sets × 8 reps × ~80 kg
    private const VOLUME_REF = 5_000.0;

    // Bodyweight proxy: assume 15 kg effective load per rep when no weight is logged
    private const BODYWEIGHT_KG = 15.0;

    // Default RPE assumed when the user logged no RPE data
    private const DEFAULT_RPE = 7.0;

    /**
     * Compute a 0–100 training score for a session.
     *
     * Components:
     *   Volume   (0–40 pts)  — total mechanical work relative to a reference session
     *   Intensity (0–30 pts) — average RPE across all sets
     *   Variety  (0–20 pts)  — number of distinct exercises × 4
     *   Duration (0–10 pts)  — session length vs. a 60-minute reference
     */
    public function scoreSession(WorkoutSession $session): int
    {
        $logs = $session->getExerciseLogs();
        if ($logs->isEmpty()) {
            return 0;
        }

        $totalVolume = 0.0;
        $rpeSum      = 0.0;
        $rpeCount    = 0;
        $exercises   = [];

        foreach ($logs as $log) {
            $reps   = $log->getReps() ?? 0;
            $weight = $log->getWeightKg() !== null ? (float) $log->getWeightKg() : null;

            $totalVolume += $reps * ($weight ?? self::BODYWEIGHT_KG);

            if ($log->getRpe() !== null) {
                $rpeSum += $log->getRpe();
                ++$rpeCount;
            }

            $exercises[$log->getExerciseName()] = true;
        }

        $avgRpe      = $rpeCount > 0 ? $rpeSum / $rpeCount : self::DEFAULT_RPE;
        $variety     = count($exercises);
        $duration    = $session->getDurationMinutes() ?? 45;

        $volumePts   = min(40.0, ($totalVolume / self::VOLUME_REF) * 40.0);
        $rpePts      = ($avgRpe / 10.0) * 30.0;
        $varietyPts  = min(20.0, $variety * 4.0);
        $durationPts = min(10.0, $duration / 6.0);

        return (int) round(min(100.0, $volumePts + $rpePts + $varietyPts + $durationPts));
    }

    public static function grade(int $score): string
    {
        return match (true) {
            $score >= 90 => 'S',
            $score >= 80 => 'A',
            $score >= 70 => 'B',
            $score >= 60 => 'C',
            $score >= 50 => 'D',
            default      => 'F',
        };
    }

    /** Hex color matching the grade tier. */
    public static function gradeColor(int $score): string
    {
        return match (true) {
            $score >= 90 => '#a78bfa', // S — purple / elite
            $score >= 80 => '#22c55e', // A — green / excellent
            $score >= 70 => '#6DB6FF', // B — blue / good
            $score >= 60 => '#E0A03E', // C — amber / average
            $score >= 50 => '#F77F00', // D — orange / light
            default      => '#555555', // F — grey / minimal
        };
    }
}
