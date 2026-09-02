<?php

declare(strict_types=1);

namespace App\Controller\Order;

use App\Attribute\RateLimiter;
use App\DTO\Response\OrderResponseDTO;
use App\Service\OrderService;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[OA\Tag('OrderController')]
#[RateLimiter(policy: 'jwt')]
#[Route('/api/v1/orders', name: 'api_list_orders', methods: ['GET'])]
final class GetOrdersAction extends AbstractController
{
    public function __construct(private readonly OrderService $orderService) {}

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
    public function __invoke(): JsonResponse
    {
        $email = $this->getUser()?->getUserIdentifier();
        if (!$email) {
            throw new UnauthorizedHttpException('Bearer', 'Користувач не авторизований');
        }

        return $this->json(['data' => $this->orderService->getList($email)]);
    }
}
