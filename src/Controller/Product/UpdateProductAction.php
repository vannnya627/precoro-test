<?php

declare(strict_types=1);

namespace App\Controller\Product;

use App\Attribute\RateLimiter;
use App\DTO\Error\ErrorResponseDTO;
use App\DTO\Request\UpdateProductRequestDTO;
use App\DTO\Response\ProductResponseDTO;
use App\Service\ProductService;
use Nelmio\ApiDocBundle\Attribute\Model;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;

#[OA\Tag('ProductController')]
#[RateLimiter]
#[Route(path: '/api/v1/product/{productId}', name: 'api_update_product', methods: ['PATCH'])]
final class UpdateProductAction extends AbstractController
{
    public function __construct(private readonly ProductService $productService) {}

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
    public function __invoke(#[MapRequestPayload] UpdateProductRequestDTO $request, int $productId): JsonResponse
    {
        return $this->json($this->productService->update($productId, $request));
    }
}
