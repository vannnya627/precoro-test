<?php

declare(strict_types=1);

namespace App\Controller\Product;

use App\Attribute\RateLimiter;
use App\DTO\Error\ErrorResponseDTO;
use App\Service\ProductService;
use Nelmio\ApiDocBundle\Attribute\Model;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;

#[OA\Tag('ProductController')]
#[RateLimiter]
#[Route(path: '/api/v1/product/{productId}', name: 'api_delete_product', methods: ['DELETE'])]
final class DeleteProductAction extends AbstractController
{
    public function __construct(private readonly ProductService $productService) {}

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
    public function __invoke(int $productId): JsonResponse
    {
        $this->productService->delete($productId);

        return $this->json(['message' => 'product deleted successfully']);
    }
}
