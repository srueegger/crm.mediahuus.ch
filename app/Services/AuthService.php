<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Repositories\UserRepository;
use Psr\Log\LoggerInterface;

class AuthService
{
    private UserRepository $userRepository;
    private LoggerInterface $logger;

    public function __construct(UserRepository $userRepository, LoggerInterface $logger)
    {
        $this->userRepository = $userRepository;
        $this->logger = $logger;
    }

    public function authenticate(string $email, string $password): ?User
    {
        // Input validation
        if (empty($email) || empty($password)) {
            $this->logger->info('Authentication failed: empty credentials', ['email' => $email]);
            return null;
        }

        // Email format validation
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->logger->info('Authentication failed: invalid email format', ['email' => $email]);
            return null;
        }

        try {
            // Find user by email
            $user = $this->userRepository->findByEmail($email);
            if (!$user) {
                $this->logger->info('Authentication failed: user not found', ['email' => $email]);
                return null;
            }

            // Check if user is active
            if (!$user->isActive()) {
                $this->logger->info('Authentication failed: user inactive', ['email' => $email]);
                return null;
            }

            // Verify password
            if (!$user->verifyPassword($password)) {
                $this->logger->info('Authentication failed: invalid password', ['email' => $email]);
                return null;
            }

            $this->logger->info('Authentication successful', ['email' => $email, 'user_id' => $user->getId()]);
            return $user;

        } catch (\Exception $e) {
            $this->logger->error('Authentication error', [
                'email' => $email,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    public function startSession(User $user): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Regenerate session ID for security
        session_regenerate_id(true);

        // Store user data in session
        $_SESSION['user_id'] = $user->getId();
        $_SESSION['user'] = $user->toArray();
        $_SESSION['login_time'] = time();

        $this->logger->info('Session started', ['user_id' => $user->getId()]);
    }

    public function endSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $userId = $_SESSION['user_id'] ?? null;
            
            // Clear session data
            session_unset();
            session_destroy();
            
            // Start new session with new ID
            session_start();
            session_regenerate_id(true);

            $this->logger->info('Session ended', ['user_id' => $userId]);
        }
    }

    public function getCurrentUser(): ?User
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            return null;
        }

        try {
            return $this->userRepository->findById($userId);
        } catch (\Exception $e) {
            $this->logger->error('Failed to get current user', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    public function isLoggedIn(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return !empty($_SESSION['user_id']);
    }

    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }
}