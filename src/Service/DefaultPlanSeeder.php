<?php

namespace App\Service;

use App\Entity\PlanDay;
use App\Entity\PlanExercise;
use App\Entity\TrainingPlan;
use App\Entity\User;
use App\Repository\TrainingPlanRepository;
use Doctrine\ORM\EntityManagerInterface;

class DefaultPlanSeeder
{
    public function __construct(
        private readonly EntityManagerInterface  $em,
        private readonly TrainingPlanRepository  $planRepo,
    ) {}

    public function seedIfNeeded(User $user): TrainingPlan
    {
        $existing = $this->planRepo->findActiveForUser($user);
        if ($existing) {
            return $existing;
        }

        $plan = new TrainingPlan();
        $plan->setUser($user);
        $plan->setName('4-Tage Split');
        $plan->setIsActive(true);

        foreach ($this->defaultDays() as $i => $dayData) {
            $day = new PlanDay();
            $day->setType($dayData['type']);
            $day->setLabel($dayData['label']);
            $day->setColor($dayData['color']);
            $day->setFocus($dayData['focus']);
            $day->setNote($dayData['note'] ?? null);
            $day->setSortOrder($i);

            foreach ($dayData['exercises'] as $j => $exData) {
                $ex = new PlanExercise();
                $ex->setName($exData['name']);
                $ex->setSection($exData['section']);
                $ex->setDefaultSets($exData['sets']);
                $ex->setDefaultReps($exData['reps'] ?? null);
                $ex->setProgressionNote($exData['progression'] ?? null);
                $ex->setIsNew($exData['new'] ?? false);
                $ex->setSortOrder($j);
                $day->addExercise($ex);
            }

            $plan->addDay($day);
        }

        $this->em->persist($plan);
        $this->em->flush();

        return $plan;
    }

    private function defaultDays(): array
    {
        return [
            [
                'type'  => 'push',
                'label' => 'Push — Brust, Schulter, Trizeps',
                'color' => '#C9184A',
                'focus' => 'Schwerer Tag · 6 Übungen · ~60 Min',
                'note'  => '💡 Streichen: Seilzug Brustpresse schräg + Seilzug Butterfly nach unten — Brust ist mit Bankdrücken + Brustpresse + Plate-Schulterdrücken ausreichend gereizt.',
                'exercises' => [
                    ['name' => 'Crosstrainer',                   'section' => 'Aufwärmen',  'sets' => 1,  'reps' => null],
                    ['name' => 'Langhantel Bankdrücken',          'section' => 'Hauptteil', 'sets' => 4,  'reps' => 7,  'progression' => '↑ steigern auf 70 kg'],
                    ['name' => 'Plate Loaded Schulterdrücken',    'section' => 'Hauptteil', 'sets' => 4,  'reps' => 9,  'progression' => '↑ Gewicht hoch'],
                    ['name' => 'Maschine Brustpresse',            'section' => 'Hauptteil', 'sets' => 3,  'reps' => 10],
                    ['name' => 'Kurzhantel Seitheben',            'section' => 'Hauptteil', 'sets' => 4,  'reps' => 13],
                    ['name' => 'Seilzug Trizepsdrücken',          'section' => 'Hauptteil', 'sets' => 3,  'reps' => 11],
                    ['name' => 'Seilzug Trizeps über Kopf',       'section' => 'Hauptteil', 'sets' => 3,  'reps' => 11, 'progression' => '↑ deutlich erhöhen'],
                ],
            ],
            [
                'type'  => 'pull',
                'label' => 'Pull — Rücken, Bizeps',
                'color' => '#2D6A4F',
                'focus' => 'Schwerer Tag · 7 Übungen · ~65 Min',
                'note'  => '💡 Streichen: Doppelte Latzug-Varianten + Seilzug Überzüge. Klimmzüge ersetzen die meisten Lat-Übungen, Bizeps-Volumen reicht mit 2 Übungen.',
                'exercises' => [
                    ['name' => 'Crosstrainer',                   'section' => 'Aufwärmen',  'sets' => 1,  'reps' => null],
                    ['name' => 'Klimmzug (oder Latzug breit)',   'section' => 'Hauptteil', 'sets' => 4,  'reps' => 8,  'progression' => 'NEU statt 3 Latzug-Varianten', 'new' => true],
                    ['name' => 'Langhantel Rudern vorgebeugt',   'section' => 'Hauptteil', 'sets' => 4,  'reps' => 8,  'progression' => '↑ schwerer trainieren'],
                    ['name' => 'Latzug neutraler Griff',         'section' => 'Hauptteil', 'sets' => 3,  'reps' => 11],
                    ['name' => 'Maschine Rudern',                'section' => 'Hauptteil', 'sets' => 3,  'reps' => 11, 'progression' => '↑ von 45 kg hoch'],
                    ['name' => 'Reverse Butterfly / Face Pulls', 'section' => 'Hauptteil', 'sets' => 3,  'reps' => 13],
                    ['name' => 'Langhantel Bizeps-Curls',        'section' => 'Hauptteil', 'sets' => 3,  'reps' => 9],
                    ['name' => 'Hammer Curls Kurzhantel',        'section' => 'Hauptteil', 'sets' => 3,  'reps' => 11],
                ],
            ],
            [
                'type'  => 'legs',
                'label' => 'Beine — Komplett',
                'color' => '#E09F3E',
                'focus' => 'Schwerster Tag · 7 Übungen · ~70 Min',
                'note'  => '💡 RDL ist die wichtigste Ergänzung — trifft Hamstrings, Glutes und unteren Rücken. Beinstrecker streichen (redundant zu Hackenschmidt + Beinpresse).',
                'exercises' => [
                    ['name' => 'Crosstrainer',               'section' => 'Aufwärmen',  'sets' => 1,  'reps' => null],
                    ['name' => 'Hackenschmidt Kniebeuge',    'section' => 'Hauptteil', 'sets' => 4,  'reps' => 8,  'progression' => '↑ von 35 kg hoch'],
                    ['name' => 'Rumänisches Kreuzheben (RDL)','section' => 'Hauptteil','sets' => 4,  'reps' => 9,  'progression' => 'NEU — Hüftstreckung!', 'new' => true],
                    ['name' => 'Beinpresse 45°',             'section' => 'Hauptteil', 'sets' => 3,  'reps' => 11, 'progression' => '↑ steigern'],
                    ['name' => 'Maschine Beinbeuger',        'section' => 'Hauptteil', 'sets' => 3,  'reps' => 12],
                    ['name' => 'Bulgarian Splits',           'section' => 'Hauptteil', 'sets' => 3,  'reps' => 10, 'progression' => '↑ von 10 kg hoch'],
                    ['name' => 'Wadenpresse sitzend',        'section' => 'Hauptteil', 'sets' => 4,  'reps' => 15],
                    ['name' => 'Adduktor / Abduktor Superset','section' => 'Hauptteil','sets' => 2,  'reps' => 15],
                ],
            ],
            [
                'type'  => 'upper',
                'label' => 'Upper — Schwachpunkte',
                'color' => '#4361EE',
                'focus' => 'Leichterer Tag · Schultern + Arme · ~50 Min',
                'note'  => '💡 Fokus auf Schultern (deine Schwachstelle) und gezielten Reiz für ästhetische Punkte. Weniger Übungen, mehr Intensität pro Übung.',
                'exercises' => [
                    ['name' => 'Crosstrainer',                        'section' => 'Aufwärmen',           'sets' => 1, 'reps' => null],
                    ['name' => 'Kurzhantel Schulterdrücken',           'section' => 'Schultern',           'sets' => 4, 'reps' => 9],
                    ['name' => 'Maschine Seitheben',                   'section' => 'Schultern',           'sets' => 4, 'reps' => 13],
                    ['name' => 'Face Pulls Seilzug',                   'section' => 'Schultern',           'sets' => 3, 'reps' => 15, 'progression' => 'NEU für hintere Schulter', 'new' => true],
                    ['name' => 'Schrägbank Kurzhantel Bankdrücken',    'section' => 'Arme + Schrägbank',  'sets' => 3, 'reps' => 10],
                    ['name' => 'Klimmzug eng / Latzug eng',            'section' => 'Arme + Schrägbank',  'sets' => 3, 'reps' => 9],
                    ['name' => 'Bizeps + Trizeps Superset',            'section' => 'Arme + Schrägbank',  'sets' => 3, 'reps' => 11],
                ],
            ],
        ];
    }
}
