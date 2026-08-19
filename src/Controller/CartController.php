<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\Error\ErrorResponseDTO;
use App\DTO\Request\AddItemRequestDTO;
use App\DTO\Response\ProductResponseDTO;
use App\Entity\User;
use App\Service\Interface\CartServiceInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[OA\Tag('CartController')]
final class CartController extends AbstractController
{
    public function __construct(
        private readonly CartServiceInterface $cartService,
    ) {
    }

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
            ]
        )
    )]
    #[OA\Response(
        response: 404,
        description: 'Product not found',
        content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
    )]
    #[OA\RequestBody(
        description: 'Request body',
        content: new OA\JsonContent(ref: new Model(type: AddItemRequestDTO::class))
    )]
    #[OA\Response(
        response: 401,
        description: 'JWT Exception',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'code', type: 'int'),
                new OA\Property(property: 'message', type: 'string'),
            ]
        )
    )]
    #[Route(path: '/api/v1/cart', name: 'api_add_product_to_cart', methods: ['POST'])]
    public function addItemToCart(#[MapRequestPayload] AddItemRequestDTO $request, #[CurrentUser] User $user): JsonResponse
    {
        $this->cartService->addItem($request, $user);

        return $this->json(['message' => 'item added success']);
    }

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
                ref: new Model(type: ProductResponseDTO::class)
            )
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'JWT Exception',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'code', type: 'int'),
                new OA\Property(property: 'message', type: 'string'),
            ]
        )
    )]
    #[Route('/api/v1/cart', name: 'api_list_items', methods: ['GET'])]
    public function getProducts(#[CurrentUser] User $user): JsonResponse
    {
        return $this->json(['data' => $this->cartService->getList($user)]);
    }
}
