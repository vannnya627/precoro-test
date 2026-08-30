<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\Error\ErrorResponseDTO;
use App\DTO\Response\OrderResponseDTO;
use App\Entity\User;
use App\Service\Interface\OrderServiceInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[OA\Tag('OrderController')]
final class OrderController extends AbstractController
{
    public function __construct(
        private readonly OrderServiceInterface $orderService,
    ) {}

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
    #[Route(path: '/api/v1/order', name: 'api_create_order', methods: ['POST'])]
    public function create(#[CurrentUser] User $user): JsonResponse
    {
        return $this->json($this->orderService->create($user));
    }

    #[OA\Get(
        operationId: 'api_list_orders',
        description: 'Отримання списку усіх замовлень для конкретного юзера',
        summary: 'Отримання списку замовлень',
    )]
    #[OA\Response(
        response: 200,
        description: 'Success',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(
                ref: new Model(type: OrderResponseDTO::class),
            ),
        ),
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
    #[Route('/api/v1/orders', name: 'api_list_orders', methods: ['GET'])]
    public function getProducts(#[CurrentUser] User $user): JsonResponse
    {
        return $this->json(['data' => $this->orderService->getList($user)]);
    }
}
