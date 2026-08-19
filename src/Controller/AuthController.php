<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\Error\ErrorResponseDTO;
use App\DTO\Request\SignUpRequestDTO;
use App\DTO\Response\SignUpResponseDTO;
use App\Service\Interface\AuthServiceInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[OA\Tag('AuthController')]
final class AuthController extends AbstractController
{
    public function __construct(
        private readonly AuthServiceInterface $authService,
    ) {
    }

    #[OA\Post(
        operationId: 'signUp',
        description: 'Реєстрація нового юзера',
        summary: 'Sign Up',
    )]
    #[OA\Response(
        response: 200,
        description: 'Sign up success',
        content: new OA\JsonContent(ref: new Model(type: SignUpResponseDTO::class))
    )]
    #[OA\Response(
        response: 409,
        description: 'User already exists',
        content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
    )]
    #[OA\Response(
        response: 422,
        description: 'Validation error',
        content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
    )]
    #[OA\RequestBody(
        description: 'Request body',
        required: true,
        content: new OA\JsonContent(ref: new Model(type: SignUpRequestDTO::class))
    )]
    #[Route('/api/v1/auth/signUp', name: 'signUp', methods: ['POST'])]
    public function signUp(#[MapRequestPayload] SignUpRequestDTO $request): JsonResponse
    {
        return $this->json($this->authService->signUp($request), 201);
    }

    #[OA\Post(
        operationId: 'login',
        description: 'Вхід користувача',
        summary: 'login',
    )]
    #[OA\Response(
        response: 200,
        description: 'Success',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'token', type: 'string'),
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'Invalid credentials',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'code', type: 'int'),
                new OA\Property(property: 'message', type: 'string'),
            ]
        )
    )]
    #[OA\RequestBody(
        description: 'Request body',
        required: true,
        content: new OA\JsonContent(
            required: ['email', 'password'],
            properties: [
                new OA\Property(property: 'email', type: 'string', example: 'admin@gmail.com'),
                new OA\Property(property: 'password', type: 'string', example: '1234567890'),
            ]
        )
    )]
    #[Route('/api/v1/auth/login', name: 'api_login', methods: ['POST'])]
    public function login(): void
    {
    }
}
