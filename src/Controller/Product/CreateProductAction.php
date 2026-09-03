<?php

declare(strict_types=1);

namespace App\Controller\Product;

use App\Attribute\RateLimiter;
use App\DTO\Error\ErrorResponseDTO;
use App\DTO\Request\ProductRequestDTO;
use App\DTO\Response\ProductResponseDTO;
use App\Service\ProductService;
use Nelmio\ApiDocBundle\Attribute\Model;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;
use Throwable;

#[OA\Tag('ProductController')]
#[RateLimiter]
#[Route(path: '/api/v1/product', name: 'api_create_product', methods: ['POST'])]
final class CreateProductAction extends AbstractController
{
    public function __construct(private readonly ProductService $productService) {}

    /**
     * @throws Throwable
     */
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
    public function __invoke(#[MapRequestPayload] ProductRequestDTO $request): JsonResponse
    {
        return $this->json($this->productService->create($request));
    }
}
