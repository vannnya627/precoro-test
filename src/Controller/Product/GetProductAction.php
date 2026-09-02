<?php

declare(strict_types=1);

namespace App\Controller\Product;

use App\Attribute\RateLimiter;
use App\DTO\Error\ErrorResponseDTO;
use App\DTO\Response\ProductResponseDTO;
use App\Service\ProductService;
use Nelmio\ApiDocBundle\Attribute\Model;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;

#[OA\Tag('ProductController')]
#[RateLimiter]
#[Route('/api/v1/product/{productId}', name: 'api_get_product', methods: ['GET'])]
final class GetProductAction extends AbstractController
{
    public function __construct(private readonly ProductService $productService) {}

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
    public function __invoke(int $productId): JsonResponse
    {
        return $this->json($this->productService->getOne($productId));
    }
}
