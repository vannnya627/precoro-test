<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\Error\ErrorResponseDTO;
use App\DTO\Request\ProductRequestDTO;
use App\DTO\Request\UpdateProductRequestDTO;
use App\DTO\Response\ProductResponseDTO;
use App\Service\Interface\ProductServiceInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[OA\Tag('ProductController')]
final class ProductController extends AbstractController
{
    public function __construct(
        private readonly ProductServiceInterface $productService,
    ) {}

    #[OA\Get(
        operationId: 'api_get_product',
        description: 'Отримання продукт за id',
        summary: 'Отримання продукту',
    )]
    #[OA\Response(
        response: 200,
        description: 'Success',
        content: new OA\JsonContent(ref: new Model(type: ProductResponseDTO::class)),
    )]
    #[OA\Response(
        response: 404,
        description: 'Product not found',
        content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class)),
    )]
    #[OA\Parameter(
        name: 'productId',
        description: 'Id Продукту',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'integer'),
    )]
    #[Route('/api/v1/product/{productId}', name: 'api_get_product', methods: ['GET'])]
    public function getProduct(int $productId): JsonResponse
    {
        return $this->json($this->productService->getOne($productId));
    }

    #[OA\Get(
        operationId: 'api_get_all_product',
        description: 'Отримання всіх продукт без пагінації',
        summary: 'Отримання всіх продуктів',
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
    #[Route('/api/v1/products', name: 'api_get_all_product', methods: ['GET'])]
    public function getProducts(): JsonResponse
    {
        return $this->json(['data' => $this->productService->getAll()]);
    }

    #[OA\Post(
        operationId: 'api_create_product',
        description: 'Створення нового продукту юзером',
        summary: 'Створення нового продукту',
    )]
    #[OA\Response(
        response: 200,
        description: 'Success',
        content: new OA\JsonContent(ref: new Model(type: ProductResponseDTO::class)),
    )]
    #[OA\Response(
        response: 422,
        description: 'Validation error',
        content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class)),
    )]
    #[OA\RequestBody(
        description: 'Request body',
        content: new OA\JsonContent(ref: new Model(type: ProductRequestDTO::class)),
    )]
    #[Route(path: '/api/v1/product', name: 'api_create_product', methods: ['POST'])]
    public function createProduct(#[MapRequestPayload] ProductRequestDTO $request): JsonResponse
    {
        return $this->json($this->productService->create($request));
    }

    #[OA\Patch(
        operationId: 'api_update_product',
        description: 'Оновлення вибіркових полів продукту',
        summary: 'Оновлення продукту',
    )]
    #[OA\Response(
        response: 200,
        description: 'Success',
        content: new OA\JsonContent(ref: new Model(type: ProductResponseDTO::class)),
    )]
    #[OA\Response(
        response: 404,
        description: 'Product not found',
        content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class)),
    )]
    #[OA\RequestBody(
        description: 'Request body',
        content: new OA\JsonContent(ref: new Model(type: UpdateProductRequestDTO::class)),
    )]
    #[OA\Parameter(
        name: 'productId',
        description: 'Id Продукту',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'integer'),
    )]
    #[Route(path: '/api/v1/product/{productId}', name: 'api_update_product', methods: ['PATCH'])]
    public function updateProduct(#[MapRequestPayload] UpdateProductRequestDTO $request, int $productId): JsonResponse
    {
        return $this->json($this->productService->update($productId, $request));
    }

    #[OA\Delete(
        operationId: 'api_delete_product',
        description: 'Видалення продукту за id',
        summary: 'Видалення',
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
    #[OA\Parameter(
        name: 'productId',
        description: 'Id Продукту',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'integer'),
    )]
    #[Route(path: '/api/v1/product/{productId}', name: 'api_delete_product', methods: ['DELETE'])]
    public function deleteProduct(int $productId): JsonResponse
    {
        $this->productService->delete($productId);

        return $this->json(['message' => 'product deleted successfully']);
    }
}
