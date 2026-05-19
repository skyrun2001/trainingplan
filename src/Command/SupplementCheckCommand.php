<?php

namespace App\Command;

use App\Entity\Supplement;
use App\Repository\SupplementRepository;
use App\Service\FcmService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:supplements:check',
    description: 'Send push notifications for low/empty supplements',
)]
class SupplementCheckCommand extends Command
{
    public function __construct(
        private readonly SupplementRepository $supplementRepo,
        private readonly FcmService           $fcm,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Print matches without sending notifications');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io     = new SymfonyStyle($input, $output);
        $dryRun = (bool) $input->getOption('dry-run');

        if ($dryRun) {
            $io->note('Dry-run mode — no notifications will be sent.');
        }

        $lowSupplements = $this->supplementRepo->findAllLowStockGlobal();

        if (empty($lowSupplements)) {
            $io->success('All supplements are well stocked. Nothing to notify.');
            return Command::SUCCESS;
        }

        // Group by user
        $byUser = [];
        foreach ($lowSupplements as $supplement) {
            $uid = $supplement->getUser()->getId();
            $byUser[$uid][] = $supplement;
        }

        $totalNotified = 0;

        foreach ($byUser as $supplements) {
            $user   = $supplements[0]->getUser();
            $empty  = array_filter($supplements, fn(Supplement $s) => $s->isEmpty());
            $low    = array_filter($supplements, fn(Supplement $s) => $s->isLow());

            $lines = [];
            foreach ($empty as $s) {
                $lines[] = "{$s->getName()} (empty)";
            }
            foreach ($low as $s) {
                $days  = round($s->daysRemaining(), 1);
                $lines[] = "{$s->getName()} (~{$days}d left)";
            }

            $count = count($lines);
            $title = $count === 1 ? 'Supplement running low' : "{$count} supplements running low";
            $body  = implode(', ', $lines);

            $io->writeln("<comment>{$user->getUsername()}</comment>: {$body}");

            if ($dryRun) {
                continue;
            }

            try {
                $sent = $this->fcm->notifyUser($user, $title, $body, [
                    'type'  => 'supplement_low',
                    'count' => (string) $count,
                ]);
                $io->writeln("  → sent to {$sent} device(s)");
                if ($sent > 0) {
                    $totalNotified++;
                }
            } catch (\RuntimeException $e) {
                $io->error("FCM error for {$user->getUsername()}: {$e->getMessage()}");
            }
        }

        if (!$dryRun) {
            $io->success("Done. Notified {$totalNotified} user(s).");
        }

        return Command::SUCCESS;
    }
}
