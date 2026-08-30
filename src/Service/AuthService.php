<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\Request\SignUpRequestDTO;
use App\DTO\Response\SignUpResponseDTO;
use App\Entity\User;
use App\Exception\UserAlreadyExistsException;
use App\Repository\Interface\UserRepositoryInterface;
use App\Service\Interface\AuthServiceInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Throwable;

final readonly class AuthService implements AuthServiceInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private PasswordHasherFactoryInterface $passwordHasherFactory,
        private JWTTokenManagerInterface $JWTTokenManager,
    ) {}

    /**
     * @throws Throwable
     */
    public function signUp(SignUpRequestDTO $request): SignUpResponseDTO
    {
        $email = $request->email;
        if ($this->userRepository->existByEmail($email)) {
            throw new UserAlreadyExistsException($email);
        }

        $hasher = $this->passwordHasherFactory->getPasswordHasher(User::class);
        $passwordHash = $hasher->hash($request->password);

        $user = User::createCustomer($email, $passwordHash);

        $this->userRepository->saveAndCommit($user);

        $token = $this->JWTTokenManager->create($user);

        return new SignUpResponseDTO(
            userId: $user->id,
            email: $user->email,
            token: $token,
        );
    }
}
