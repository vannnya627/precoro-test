<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\DTO\Request\SignUpRequestDTO;
use App\DTO\Response\SignUpResponseDTO;
use App\Entity\User;
use App\Exception\UserAlreadyExistsException;
use App\Repository\Interface\UserRepositoryInterface;
use App\Service\AuthService;
use App\Tests\AbstractTestCase;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AllowMockObjectsWithoutExpectations]
class AuthServiceTest extends AbstractTestCase
{
    private UserRepositoryInterface|MockObject $userRepository;
    private UserPasswordHasherInterface|MockObject $passwordHasher;
    private JWTTokenManagerInterface|MockObject $JWTTokenManager;
    private AuthService $authService;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $this->JWTTokenManager = $this->createMock(JWTTokenManagerInterface::class);
        $this->authService = new AuthService($this->userRepository, $this->passwordHasher, $this->JWTTokenManager);
    }

    /**
     * @throws \Throwable
     */
    public function testSignUp()
    {
        $request = new SignUpRequestDTO(
            'test@test.com',
            '1234567890'
        );

        $passwordHash = 'gdfgergerer3r34t4gffrerhg';

        $this->userRepository->expects($this->once())
            ->method('existByEmail')
            ->with($request->email)
            ->willReturn(null);

        $this->passwordHasher->expects($this->once())
            ->method('hashPassword')
            ->with($this->isInstanceOf(User::class), $request->password)
            ->willReturn($passwordHash);

        $userId = 1;
        $user = new User()
            ->setPassword($passwordHash)
            ->setEmail($request->email)
            ->setRoles(['ROLE_USER']);
        $this->setEntityId($user, $userId);

        $this->userRepository->expects($this->once())
            ->method('saveAndCommit')
            ->willReturnCallback(function (User $savedUser) use ($request) {
                $this->assertEquals($request->email, $savedUser->getEmail());

                $this->setEntityId($savedUser, 1);
            });

        $token = 'testToken';
        $this->JWTTokenManager->expects($this->once())
            ->method('create')
            ->with($user)
            ->willReturn($token);

        $expectedResponse = new SignUpResponseDTO(
            userId: $user->getId(),
            email: $user->getEmail(),
            token: $token
        );
        $result = $this->authService->signUp($request);
        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * @throws \ReflectionException
     * @throws \Throwable
     */
    public function testSignUpThrowsUserAlreadyExistsException()
    {
        $request = new SignUpRequestDTO(
            'test@test.com',
            '1234567890'
        );

        $passwordHash = 'gdfgergerer3r34t4gffrerhg';

        $user = new User()
            ->setPassword($passwordHash)
            ->setEmail($request->email)
            ->setRoles(['ROLE_USER']);
        $userId = 1;
        $this->setEntityId($user, $userId);

        $this->userRepository->expects($this->once())
            ->method('existByEmail')
            ->with($request->email)
            ->willReturn($user);

        $this->passwordHasher->expects($this->never())
            ->method('hashPassword');

        $this->userRepository->expects($this->never())
            ->method('saveAndCommit');

        $this->JWTTokenManager->expects($this->never())
            ->method('create');

        $this->expectException(UserAlreadyExistsException::class);
        $this->authService->signUp($request);
    }
}
