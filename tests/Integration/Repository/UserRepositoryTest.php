<?php

namespace App\Tests\Integration\Repository;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Tests\Integration\IntegrationTestCase;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;

class UserRepositoryTest extends IntegrationTestCase
{
    private UserRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = self::getContainer()->get(UserRepository::class);
    }

    private function makeUser(string $username, string $password = 'hashed'): User
    {
        $u = new User();
        $u->setUsername($username);
        $u->setPassword($password);
        return $u;
    }

    public function testCountAllReturnsZeroForEmptyDb(): void
    {
        $this->assertSame(0, $this->repo->countAll());
    }

    public function testCountAllReturnsCorrectCount(): void
    {
        $this->persist($this->makeUser('alice'), $this->makeUser('bob'));
        $this->assertSame(2, $this->repo->countAll());
    }

    public function testFindOneByUsername(): void
    {
        $this->persist($this->makeUser('charlie'));

        $found = $this->repo->findOneBy(['username' => 'charlie']);

        $this->assertNotNull($found);
        $this->assertSame('charlie', $found->getUsername());
    }

    public function testUpgradePasswordUpdatesHash(): void
    {
        $user = $this->makeUser('upgradetest', 'old_hash');
        $this->persist($user);

        $this->repo->upgradePassword($user, 'new_hash');

        $this->em->clear();
        $refreshed = $this->repo->findOneBy(['username' => 'upgradetest']);
        $this->assertSame('new_hash', $refreshed->getPassword());
    }

    public function testUpgradePasswordThrowsForUnsupportedUser(): void
    {
        $unsupported = $this->createStub(\Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface::class);

        $this->expectException(UnsupportedUserException::class);
        $this->repo->upgradePassword($unsupported, 'new_hash');
    }
}
