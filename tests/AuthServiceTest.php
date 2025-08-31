<?php
declare(strict_types=1);

namespace Tests;

use App\Services\AuthService;
use App\Models\User;
use App\Repositories\UserRepository;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class AuthServiceTest extends TestCase
{
    private UserRepository|MockObject $userRepository;
    private LoggerInterface|MockObject $logger;
    private AuthService $authService;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->authService = new AuthService($this->userRepository, $this->logger);
    }

    public function testAuthenticateWithValidCredentials(): void
    {
        $email = 'test@example.com';
        $password = 'password123';
        $hashedPassword = AuthService::hashPassword($password);
        
        $user = new User('Test User', $email, $hashedPassword, true, 1);
        
        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn($user);

        $result = $this->authService->authenticate($email, $password);
        
        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals($email, $result->getEmail());
    }

    public function testAuthenticateWithInvalidPassword(): void
    {
        $email = 'test@example.com';
        $password = 'password123';
        $wrongPassword = 'wrongpassword';
        $hashedPassword = AuthService::hashPassword($password);
        
        $user = new User('Test User', $email, $hashedPassword, true, 1);
        
        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn($user);

        $result = $this->authService->authenticate($email, $wrongPassword);
        
        $this->assertNull($result);
    }

    public function testAuthenticateWithNonExistentUser(): void
    {
        $email = 'nonexistent@example.com';
        $password = 'password123';
        
        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn(null);

        $result = $this->authService->authenticate($email, $password);
        
        $this->assertNull($result);
    }

    public function testAuthenticateWithEmptyCredentials(): void
    {
        $result1 = $this->authService->authenticate('', 'password');
        $result2 = $this->authService->authenticate('test@example.com', '');
        $result3 = $this->authService->authenticate('', '');
        
        $this->assertNull($result1);
        $this->assertNull($result2);
        $this->assertNull($result3);
    }

    public function testHashPassword(): void
    {
        $password = 'testpassword123';
        $hash = AuthService::hashPassword($password);
        
        $this->assertTrue(password_verify($password, $hash));
        $this->assertNotEquals($password, $hash);
    }
}