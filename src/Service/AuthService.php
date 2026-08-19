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
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final readonly class AuthService implements AuthServiceInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
        private JWTTokenManagerInterface $JWTTokenManager,
    ) {
    }

    /**
     * @throws \Throwable
     */
    public function signUp(SignUpRequestDTO $request): SignUpResponseDTO
    {
        if ($this->userRepository->existByEmail($request->email)) {
            throw new UserAlreadyExistsException();
        }

        $user = new User()
            ->setEmail($request->email)
            ->setRoles(['ROLE_USER']);

        $passwordHash = $this->passwordHasher->hashPassword($user, $request->password);
        $user->setPassword($passwordHash);

        $this->userRepository->saveAndCommit($user);

        $token = $this->JWTTokenManager->create($user);

        return new SignUpResponseDTO(
            userId: $user->getId(),
            email: $user->getEmail(),
            token: $token
        );
    }
}
