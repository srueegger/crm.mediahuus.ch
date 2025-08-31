<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\AuthService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Twig\Environment;

class UserController extends BaseController
{
    private UserRepository $userRepository;
    private LoggerInterface $logger;

    public function __construct(Environment $twig, UserRepository $userRepository, LoggerInterface $logger)
    {
        parent::__construct($twig);
        $this->userRepository = $userRepository;
        $this->logger = $logger;
    }

    public function index(Request $request, Response $response): Response
    {
        /** @var User $currentUser */
        $currentUser = $request->getAttribute('current_user');
        
        try {
            $users = $this->userRepository->findAll(true); // Include inactive users
            
            return $this->render($response, 'users/index.html.twig', [
                'user' => $currentUser->toArray(),
                'users' => array_map(fn($user) => $user->toArray(), $users),
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to fetch users', ['error' => $e->getMessage()]);
            
            return $this->render($response, 'users/index.html.twig', [
                'user' => $currentUser->toArray(),
                'users' => [],
                'error' => 'Fehler beim Laden der Benutzerliste'
            ]);
        }
    }

    public function create(Request $request, Response $response): Response
    {
        /** @var User $currentUser */
        $currentUser = $request->getAttribute('current_user');
        
        return $this->render($response, 'users/create.html.twig', [
            'user' => $currentUser->toArray(),
        ]);
    }

    public function store(Request $request, Response $response): Response
    {
        /** @var User $currentUser */
        $currentUser = $request->getAttribute('current_user');
        $data = $request->getParsedBody();
        
        // Validate input
        $errors = $this->validateUserInput($data);
        
        // Check if email already exists
        if (empty($errors) && $this->userRepository->emailExists($data['email'] ?? '')) {
            $errors['email'] = 'E-Mail-Adresse wird bereits verwendet';
        }
        
        if (!empty($errors)) {
            return $this->render($response, 'users/create.html.twig', [
                'user' => $currentUser->toArray(),
                'errors' => $errors,
                'formData' => $data,
            ]);
        }

        try {
            $newUser = new User(
                name: trim($data['name']),
                email: trim($data['email']),
                passwordHash: AuthService::hashPassword($data['password']),
                isActive: !empty($data['is_active'])
            );

            $userId = $this->userRepository->create($newUser);
            
            $this->logger->info('User created', [
                'new_user_id' => $userId,
                'created_by' => $currentUser->getId()
            ]);

            return $response->withHeader('Location', '/users?success=created')->withStatus(302);
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to create user', [
                'error' => $e->getMessage(),
                'created_by' => $currentUser->getId()
            ]);
            
            return $this->render($response, 'users/create.html.twig', [
                'user' => $currentUser->toArray(),
                'error' => 'Fehler beim Erstellen des Benutzers',
                'formData' => $data,
            ]);
        }
    }

    public function edit(Request $request, Response $response, array $args): Response
    {
        /** @var User $currentUser */
        $currentUser = $request->getAttribute('current_user');
        $userId = (int) $args['id'];
        
        try {
            $editUser = $this->userRepository->findById($userId);
            if (!$editUser) {
                return $response->withHeader('Location', '/users?error=notfound')->withStatus(302);
            }
            
            return $this->render($response, 'users/edit.html.twig', [
                'user' => $currentUser->toArray(),
                'editUser' => $editUser->toArray(),
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to fetch user for edit', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            
            return $response->withHeader('Location', '/users?error=fetch')->withStatus(302);
        }
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        /** @var User $currentUser */
        $currentUser = $request->getAttribute('current_user');
        $userId = (int) $args['id'];
        $data = $request->getParsedBody();
        
        try {
            $editUser = $this->userRepository->findById($userId);
            if (!$editUser) {
                return $response->withHeader('Location', '/users?error=notfound')->withStatus(302);
            }
            
            // Validate input
            $errors = $this->validateUserInput($data, false); // Password optional for edit
            
            // Check if email already exists (excluding current user)
            if (empty($errors) && $this->userRepository->emailExists($data['email'] ?? '', $userId)) {
                $errors['email'] = 'E-Mail-Adresse wird bereits verwendet';
            }
            
            if (!empty($errors)) {
                return $this->render($response, 'users/edit.html.twig', [
                    'user' => $currentUser->toArray(),
                    'editUser' => array_merge($editUser->toArray(), $data),
                    'errors' => $errors,
                ]);
            }

            // Update user data
            $passwordHash = !empty($data['password']) 
                ? AuthService::hashPassword($data['password'])
                : $editUser->getPasswordHash();

            $updatedUser = new User(
                name: trim($data['name']),
                email: trim($data['email']),
                passwordHash: $passwordHash,
                isActive: !empty($data['is_active']),
                id: $userId,
                createdAt: $editUser->getCreatedAt(),
                updatedAt: new \DateTime()
            );

            $success = $this->userRepository->update($updatedUser);
            
            if ($success) {
                $this->logger->info('User updated', [
                    'user_id' => $userId,
                    'updated_by' => $currentUser->getId()
                ]);
                
                return $response->withHeader('Location', '/users?success=updated')->withStatus(302);
            } else {
                throw new \RuntimeException('Update failed');
            }
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to update user', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'updated_by' => $currentUser->getId()
            ]);
            
            return $response->withHeader('Location', '/users?error=update')->withStatus(302);
        }
    }

    public function toggleStatus(Request $request, Response $response, array $args): Response
    {
        /** @var User $currentUser */
        $currentUser = $request->getAttribute('current_user');
        $userId = (int) $args['id'];
        $data = $request->getParsedBody();
        $action = $data['action'] ?? '';
        
        // Prevent self-deactivation
        if ($userId === $currentUser->getId()) {
            return $response->withHeader('Location', '/users?error=self_action')->withStatus(302);
        }
        
        try {
            $success = match($action) {
                'activate' => $this->userRepository->activate($userId),
                'deactivate' => $this->userRepository->deactivate($userId),
                default => false
            };
            
            if ($success) {
                $this->logger->info('User status changed', [
                    'user_id' => $userId,
                    'action' => $action,
                    'changed_by' => $currentUser->getId()
                ]);
                
                return $response->withHeader('Location', '/users?success=' . $action . 'd')->withStatus(302);
            } else {
                throw new \RuntimeException('Status change failed');
            }
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to change user status', [
                'user_id' => $userId,
                'action' => $action,
                'error' => $e->getMessage(),
                'changed_by' => $currentUser->getId()
            ]);
            
            return $response->withHeader('Location', '/users?error=status_change')->withStatus(302);
        }
    }

    private function validateUserInput(array $data, bool $passwordRequired = true): array
    {
        $errors = [];
        
        if (empty(trim($data['name'] ?? ''))) {
            $errors['name'] = 'Name ist erforderlich';
        }
        
        $email = trim($data['email'] ?? '');
        if (empty($email)) {
            $errors['email'] = 'E-Mail-Adresse ist erforderlich';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Ungültige E-Mail-Adresse';
        }
        
        if ($passwordRequired) {
            $password = $data['password'] ?? '';
            if (empty($password)) {
                $errors['password'] = 'Passwort ist erforderlich';
            } elseif (strlen($password) < 6) {
                $errors['password'] = 'Passwort muss mindestens 6 Zeichen lang sein';
            }
        } else {
            // For updates, validate password only if provided
            $password = $data['password'] ?? '';
            if (!empty($password) && strlen($password) < 6) {
                $errors['password'] = 'Passwort muss mindestens 6 Zeichen lang sein';
            }
        }
        
        return $errors;
    }
}