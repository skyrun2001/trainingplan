<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(name: 'app:create-user', description: 'Create a new user account')]
class CreateUserCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface      $em,
        private readonly UserPasswordHasherInterface $hasher,
        private readonly UserRepository              $userRepo,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('username', InputArgument::REQUIRED, 'Username (letters, numbers, _ - .)')
            ->addArgument('password', InputArgument::OPTIONAL, 'Password (prompted securely if omitted)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io       = new SymfonyStyle($input, $output);
        $username = trim($input->getArgument('username'));

        if (!preg_match('/^[a-zA-Z0-9_\-\.]{3,80}$/', $username)) {
            $io->error('Invalid username. Use letters, numbers, _ - .  (3–80 chars).');
            return Command::FAILURE;
        }

        if ($this->userRepo->findOneBy(['username' => $username])) {
            $io->error("User \"{$username}\" already exists.");
            return Command::FAILURE;
        }

        $password = $input->getArgument('password')
            ?? $io->askHidden('Password (min. 6 characters)');

        if (!$password || strlen($password) < 6) {
            $io->error('Password must be at least 6 characters.');
            return Command::FAILURE;
        }

        $user = new User();
        $user->setUsername($username);
        $user->setPassword($this->hasher->hashPassword($user, $password));

        $this->em->persist($user);
        $this->em->flush();

        $io->success("User \"{$username}\" created. They can now log in at /login.");
        return Command::SUCCESS;
    }
}
