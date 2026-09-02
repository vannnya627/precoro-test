<?php

declare(strict_types=1);

namespace App\Controller\Product;

use App\Attribute\RateLimiter;
use App\DTO\Response\ProductResponseDTO;
use App\Service\ProductService;
use Nelmio\ApiDocBundle\Attribute\Model;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;

#[OA\Tag('ProductController')]
#[RateLimiter]
#[Route('/api/v1/products', name: 'api_get_all_product', methods: ['GET'])]
final class GetProductsAction extends AbstractController
{
    public function __construct(private readonly ProductService $productService) {}

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
    public function __invoke(): JsonResponse
    {
        return $this->json(['data' => $this->productService->getAll()]);
    }
}
