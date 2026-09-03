<?php

declare(strict_types=1);

namespace App\Controller\Cart;

use App\Attribute\RateLimiter;
use App\DTO\Response\ProductResponseDTO;
use App\Service\CartService;
use App\ValueObject\Email;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[OA\Tag('CartController')]
#[RateLimiter(policy: 'jwt')]
#[Route('/api/v1/cart', name: 'api_list_items', methods: ['GET'])]
final class GetCartItemsAction extends AbstractController
{
    public function __construct(private readonly CartService $cartService) {}

    #[OA\Get(
        operationId: 'api_list_items',
        description: 'Отримання списку товарів у кошику для конкретного юзера',
        summary: 'Отримання списку товарів',
    )]
    #[OA\Response(
        response: 200,
        description: 'Success',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(
                ref: new Model(type: ProductResponseDTO::class),
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
        $emailStr = $this->getUser()?->getUserIdentifier();
        if (!$emailStr) {
            throw new UnauthorizedHttpException('Bearer', 'Користувач не авторизований');
        }

        return $this->json(['data' => $this->cartService->getList(Email::create($emailStr))]);
    }
}
