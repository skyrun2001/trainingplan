<?php

namespace App\Tests\Unit\Security;

use App\Repository\ApiTokenRepository;
use App\Security\ApiTokenAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

#[AllowMockObjectsWithoutExpectations]
class ApiTokenAuthenticatorTest extends TestCase
{
    private ApiTokenAuthenticator $authenticator;

    protected function setUp(): void
    {
        $repo = $this->createMock(ApiTokenRepository::class);
        $em   = $this->createMock(EntityManagerInterface::class);
        $this->authenticator = new ApiTokenAuthenticator($repo, $em);
    }

    public function testSupportsTrueWithBearerToken(): void
    {
        $request = new Request();
        $request->headers->set('Authorization', 'Bearer abc123');

        $this->assertTrue($this->authenticator->supports($request));
    }

    public function testSupportsFalseWithNoAuthorizationHeader(): void
    {
        $request = new Request();

        $this->assertFalse($this->authenticator->supports($request));
    }

    public function testSupportsFalseWithBasicAuth(): void
    {
        $request = new Request();
        $request->headers->set('Authorization', 'Basic dXNlcjpwYXNz');

        $this->assertFalse($this->authenticator->supports($request));
    }

    public function testSupportsFalseWithEmptyHeader(): void
    {
        $request = new Request();
        $request->headers->set('Authorization', '');

        $this->assertFalse($this->authenticator->supports($request));
    }

    public function testOnAuthenticationFailureReturnsUnauthorizedJson(): void
    {
        $request   = new Request();
        $exception = new AuthenticationException('bad token');

        $response = $this->authenticator->onAuthenticationFailure($request, $exception);

        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\JsonResponse::class, $response);
        $this->assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());

        $body = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('error', $body);
    }

    public function testOnAuthenticationSuccessReturnsNull(): void
    {
        $request  = new Request();
        $token    = $this->createMock(\Symfony\Component\Security\Core\Authentication\Token\TokenInterface::class);

        $result = $this->authenticator->onAuthenticationSuccess($request, $token, 'api');

        $this->assertNull($result);
    }
}
