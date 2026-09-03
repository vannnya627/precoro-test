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
use App\ValueObject\Email;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionException;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;
use Throwable;

#[AllowMockObjectsWithoutExpectations]
class AuthServiceTest extends AbstractTestCase
{
    private UserRepositoryInterface|MockObject $userRepository;
    private PasswordHasherFactoryInterface|MockObject $passwordHasherFactory;
    private JWTTokenManagerInterface|MockObject $JWTTokenManager;
    private AuthService $authService;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->passwordHasherFactory = $this->createMock(PasswordHasherFactoryInterface::class);
        $this->JWTTokenManager = $this->createMock(JWTTokenManagerInterface::class);
        $this->authService = new AuthService($this->userRepository, $this->passwordHasherFactory, $this->JWTTokenManager);
    }

    /**
     * @throws Throwable
     */
    public function testSignUp()
    {
        $request = new SignUpRequestDTO(
            'test@test.com',
            '1234567890',
        );

        $passwordHash = 'gdfgergerer3r34t4gffrerhg';

        $email = Email::create($request->email);
        $this->userRepository->expects($this->once())
            ->method('existByEmail')
            ->with($email)
            ->willReturn(false);

        $hasherMock = $this->createStub(PasswordHasherInterface::class);
        $this->passwordHasherFactory->expects($this->once())
            ->method('getPasswordHasher')
            ->with(User::class)
            ->willReturn($hasherMock);

        $user = $this->createUser($email, $passwordHash);

        $this->userRepository->expects($this->once())
            ->method('saveAndCommit')
            ->willReturnCallback(function (User $savedUser) use ($email) {
                $this->assertEquals($email->value, $savedUser->email->value);

                $this->setEntityId($savedUser, 1);
            });

        $token = 'testToken';
        $this->JWTTokenManager->expects($this->once())
            ->method('create')
            ->with($this->callback(fn(User $createdUser) => 'test@test.com' === $createdUser->email->value))
            ->willReturn($token);

        $expectedResponse = new SignUpResponseDTO(
            userId: $user->id,
            email: $user->email->value,
            token: $token,
        );
        $result = $this->authService->signUp($request);
        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * @throws ReflectionException
     * @throws Throwable
     */
    public function testSignUpThrowsUserAlreadyExistsException()
    {
        $request = new SignUpRequestDTO(
            'test@test.com',
            '1234567890',
        );

        $passwordHash = 'gdfgergerer3r34t4gffrerhg';

        $email = Email::create($request->email);
        $user = $this->createUser($email, $passwordHash);

        $this->userRepository->expects($this->once())
            ->method('existByEmail')
            ->with($email)
            ->willReturn(true);

        $this->passwordHasherFactory->expects($this->never())
            ->method('getPasswordHasher');

        $this->userRepository->expects($this->never())
            ->method('saveAndCommit');

        $this->JWTTokenManager->expects($this->never())
            ->method('create');

        $this->expectException(UserAlreadyExistsException::class);
        $this->authService->signUp($request);
    }

    /**
     * @throws ReflectionException
     */
    private function createUser(Email $email, string $passwordHash): User
    {
        $userId = 1;
        $user = User::createCustomer($email, $passwordHash);
        $this->setEntityId($user, $userId);

        return $user;
    }
}
