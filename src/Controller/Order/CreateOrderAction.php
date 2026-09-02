<?php

declare(strict_types=1);

namespace App\Controller\Order;

use App\Attribute\RateLimiter;
use App\DTO\Error\ErrorResponseDTO;
use App\DTO\Response\OrderResponseDTO;
use App\Entity\User;
use App\Service\OrderService;
use Nelmio\ApiDocBundle\Attribute\Model;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use OpenApi\Attributes as OA;

#[OA\Tag('OrderController')]
#[RateLimiter(policy: 'jwt')]
#[Route(path: '/api/v1/order', name: 'api_create_order', methods: ['POST'])]
final class CreateOrderAction extends AbstractController
{
    public function __construct(private readonly OrderService $orderService) {}

    #[OA\Post(
        operationId: 'api_create_order',
        description: 'Створення замовлення на основі товарі у кошику певного користувача',
        summary: 'Створення замовлення',
    )]
    #[OA\Response(
        response: 200,
        description: 'Success',
        content: new OA\JsonContent(ref: new Model(type: OrderResponseDTO::class)),
    )]
    #[OA\Response(
        response: 422,
        description: 'Cart Is Empty',
        content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class)),
    )]
    #[OA\Response(
        response: 401,
        description: 'JWT Exception',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'code', type: 'int'),
                new OA\Property(property: 'message', type: 'string'),
            ],
        ),
    )]
    public function __invoke(#[CurrentUser] User $user): JsonResponse
    {
        return $this->json($this->orderService->create($user));
    }
}
