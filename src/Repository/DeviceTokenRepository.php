<?php

namespace App\Repository;

use App\Entity\DeviceToken;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DeviceToken>
 */
class DeviceTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DeviceToken::class);
    }

    /** @return DeviceToken[] */
    public function findForUser(User $user): array
    {
        return $this->findBy(['user' => $user]);
    }

    public function findOneByToken(string $token): ?DeviceToken
    {
        return $this->findOneBy(['token' => $token]);
    }
}
