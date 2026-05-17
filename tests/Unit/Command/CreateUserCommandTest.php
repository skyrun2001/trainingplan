<?php

namespace App\Tests\Unit\Command;

use App\Command\CreateUserCommand;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AllowMockObjectsWithoutExpectations]
class CreateUserCommandTest extends TestCase
{
    private EntityManagerInterface      $em;
    private UserPasswordHasherInterface $hasher;
    private UserRepository              $userRepo;
    private CreateUserCommand           $command;

    protected function setUp(): void
    {
        $this->em       = $this->createMock(EntityManagerInterface::class);
        $this->hasher   = $this->createMock(UserPasswordHasherInterface::class);
        $this->userRepo = $this->createMock(UserRepository::class);

        $this->hasher->method('hashPassword')->willReturn('hashed_password');

        $this->command = new CreateUserCommand($this->em, $this->hasher, $this->userRepo);
    }

    public function testSuccessfulUserCreation(): void
    {
        $this->userRepo->method('findOneBy')->willReturn(null);
        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $tester = new CommandTester($this->command);
        $code   = $tester->execute(['username' => 'newuser', 'password' => 'securepass']);

        $this->assertSame(Command::SUCCESS, $code);
        $this->assertStringContainsString('created', $tester->getDisplay());
    }

    public function testFailsWithInvalidUsernameTooShort(): void
    {
        $tester = new CommandTester($this->command);
        $code   = $tester->execute(['username' => 'ab', 'password' => 'securepass']);

        $this->assertSame(Command::FAILURE, $code);
        $this->assertStringContainsString('Invalid username', $tester->getDisplay());
    }

    public function testFailsWithInvalidUsernameSpecialChars(): void
    {
        $tester = new CommandTester($this->command);
        $code   = $tester->execute(['username' => 'user name!', 'password' => 'securepass']);

        $this->assertSame(Command::FAILURE, $code);
    }

    public function testFailsWhenUserAlreadyExists(): void
    {
        $existing = new User();
        $existing->setUsername('existing');
        $this->userRepo->method('findOneBy')->willReturn($existing);

        $tester = new CommandTester($this->command);
        $code   = $tester->execute(['username' => 'existing', 'password' => 'securepass']);

        $this->assertSame(Command::FAILURE, $code);
        $this->assertStringContainsString('already exists', $tester->getDisplay());
    }

    public function testFailsWithPasswordTooShort(): void
    {
        $this->userRepo->method('findOneBy')->willReturn(null);

        $tester = new CommandTester($this->command);
        $code   = $tester->execute(['username' => 'validuser', 'password' => '123']);

        $this->assertSame(Command::FAILURE, $code);
        $this->assertStringContainsString('6 characters', $tester->getDisplay());
    }

    public function testHasherIsCalledWithCorrectArguments(): void
    {
        $this->userRepo->method('findOneBy')->willReturn(null);

        $this->hasher->expects($this->once())
            ->method('hashPassword')
            ->with($this->isInstanceOf(User::class), 'mypassword')
            ->willReturn('hashed');

        $tester = new CommandTester($this->command);
        $tester->execute(['username' => 'gooduser', 'password' => 'mypassword']);
    }

    public function testUsernameWith80CharsIsValid(): void
    {
        $this->userRepo->method('findOneBy')->willReturn(null);
        $this->em->method('persist');
        $this->em->method('flush');

        $longName = str_repeat('a', 80);
        $tester   = new CommandTester($this->command);
        $code     = $tester->execute(['username' => $longName, 'password' => 'securepass']);

        $this->assertSame(Command::SUCCESS, $code);
    }

    public function testUsernameWith81CharsIsInvalid(): void
    {
        $tester = new CommandTester($this->command);
        $code   = $tester->execute(['username' => str_repeat('a', 81), 'password' => 'securepass']);

        $this->assertSame(Command::FAILURE, $code);
    }

    public function testValidUsernameAllowsDotsAndDashes(): void
    {
        $this->userRepo->method('findOneBy')->willReturn(null);
        $this->em->method('persist');
        $this->em->method('flush');

        $tester = new CommandTester($this->command);
        $code   = $tester->execute(['username' => 'user.name-ok_1', 'password' => 'securepass']);

        $this->assertSame(Command::SUCCESS, $code);
    }
}
