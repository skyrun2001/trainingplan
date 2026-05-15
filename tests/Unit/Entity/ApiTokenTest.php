<?php

namespace App\Tests\Unit\Entity;

use App\Entity\ApiToken;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class ApiTokenTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        $this->user = new User();
        $this->user->setUsername('tokenuser');
    }

    public function testConstructorGenerates64CharHexToken(): void
    {
        $token = new ApiToken($this->user);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $token->getToken());
    }

    public function testConstructorSetsUser(): void
    {
        $token = new ApiToken($this->user);
        $this->assertSame($this->user, $token->getUser());
    }

    public function testConstructorSetsCreatedAtToNow(): void
    {
        $before = new \DateTime();
        $token  = new ApiToken($this->user);
        $after  = new \DateTime();

        $this->assertGreaterThanOrEqual($before->getTimestamp(), $token->getCreatedAt()->getTimestamp());
        $this->assertLessThanOrEqual($after->getTimestamp(), $token->getCreatedAt()->getTimestamp());
    }

    public function testConstructorSetsExpiresAtTo90Days(): void
    {
        $token    = new ApiToken($this->user);
        $expected = (new \DateTime())->modify('+90 days');

        $this->assertEqualsWithDelta(
            $expected->getTimestamp(),
            $token->getExpiresAt()->getTimestamp(),
            5
        );
    }

    public function testIsExpiredReturnsFalseForFreshToken(): void
    {
        $token = new ApiToken($this->user);
        $this->assertFalse($token->isExpired());
    }

    public function testIsExpiredReturnsTrueForPastToken(): void
    {
        $token = new ApiToken($this->user);

        $ref = new \ReflectionProperty(ApiToken::class, 'expiresAt');
        $ref->setValue($token, new \DateTime('-1 day'));

        $this->assertTrue($token->isExpired());
    }

    public function testEachTokenIsUnique(): void
    {
        $a = new ApiToken($this->user);
        $b = new ApiToken($this->user);
        $this->assertNotSame($a->getToken(), $b->getToken());
    }

    public function testIdIsNullBeforePersist(): void
    {
        $token = new ApiToken($this->user);
        $this->assertNull($token->getId());
    }
}
