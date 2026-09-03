<?php

declare(strict_types=1);

namespace App\Controller\Cart;

use App\Attribute\RateLimiter;
use App\DTO\Error\ErrorResponseDTO;
use App\DTO\Request\AddItemRequestDTO;
use App\Service\CartService;
use App\ValueObject\Email;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[OA\Tag('CartController')]
#[RateLimiter(policy: 'jwt')]
#[Route(path: '/api/v1/cart', name: 'api_add_product_to_cart', methods: ['POST'])]
final class AddItemToCartAction extends AbstractController
{
    public function __construct(private readonly CartService $cartService) {}

    #[OA\Post(
        operationId: 'api_add_product_to_cart',
        description: 'Додавання товару до кошика користувача',
        summary: 'Створення нового продукту',
    )]
    #[OA\Response(
        response: 200,
        description: 'Success',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string'),
            ],
        ),
    )]
    #[OA\Response(
        response: 404,
        description: 'Product not found',
        content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class)),
    )]
    #[OA\RequestBody(
        description: 'Request body',
        content: new OA\JsonContent(ref: new Model(type: AddItemRequestDTO::class)),
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
    public function __invoke(#[MapRequestPayload] AddItemRequestDTO $request): JsonResponse
    {
        $emailStr = $this->getUser()?->getUserIdentifier();
        if (!$emailStr) {
            throw new UnauthorizedHttpException('Bearer', 'Користувач не авторизований');
        }

        $this->cartService->addItem($request, Email::create($emailStr));

        return $this->json(['message' => 'item added success']);
    }
}
