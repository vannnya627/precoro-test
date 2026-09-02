<?php

declare(strict_types=1);

namespace App\Controller\Auth;

use App\Attribute\RateLimiter;
use App\DTO\Error\ErrorResponseDTO;
use App\DTO\Request\SignUpRequestDTO;
use App\DTO\Response\SignUpResponseDTO;
use App\Service\AuthService;
use Nelmio\ApiDocBundle\Attribute\Model;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;

#[OA\Tag('AuthController')]
#[RateLimiter(policy: 'auth')]
#[Route('/api/v1/auth/signUp', name: 'signUp', methods: ['POST'])]
final class SignUpAction extends AbstractController
{
    public function __construct(private readonly AuthService $authService) {}

    #[OA\Post(
        operationId: 'signUp',
        description: 'Реєстрація нового юзера',
        summary: 'Реєстрація',
    )]
    #[OA\Response(
        response: 200,
        description: 'Sign up success',
        content: new OA\JsonContent(ref: new Model(type: SignUpResponseDTO::class)),
    )]
    #[OA\Response(
        response: 409,
        description: 'User already exists',
        content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class)),
    )]
    #[OA\Response(
        response: 422,
        description: 'Validation error',
        content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class)),
    )]
    #[OA\RequestBody(
        description: 'Request body',
        required: true,
        content: new OA\JsonContent(ref: new Model(type: SignUpRequestDTO::class)),
    )]
    public function __invoke(#[MapRequestPayload] SignUpRequestDTO $request): JsonResponse
    {
        return $this->json($this->authService->signUp($request), 201);
    }
}
