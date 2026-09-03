<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\Request\ProductRequestDTO;
use App\DTO\Request\UpdateProductRequestDTO;
use App\DTO\Response\ProductResponseDTO;
use App\Entity\Product;
use App\Repository\Interface\ProductRepositoryInterface;
use App\ValueObject\Price;
use Throwable;

final readonly class ProductService
{
    public function __construct(
        private ProductRepositoryInterface $productRepository,
    ) {}

    public function getOne(int $productId): ProductResponseDTO
    {
        $product = $this->productRepository->getById($productId);

        return ProductResponseDTO::create($product);
    }

    /**
     * @throws Throwable
     */
    public function create(ProductRequestDTO $request): ProductResponseDTO
    {
        $product = Product::create($request->name, $request->description, Price::create($request->price));
        $this->productRepository->saveAndCommit($product);

        return ProductResponseDTO::create($product);
    }

    /**
     * @return list<ProductResponseDTO>
     */
    public function getAll(): array
    {
        return array_map(callback: ProductResponseDTO::create(...), array: $this->productRepository->findProducts());
    }

    /**
     * @throws Throwable
     */
    public function update(int $productId, UpdateProductRequestDTO $request): ProductResponseDTO
    {
        $product = $this->productRepository->getById($productId);
        $product->update(
            newName: $request->name,
            newDescription: $request->description,
            newPrice: (null !== $request->price) ? Price::create($request->price) : null,
        );

        $this->productRepository->saveAndCommit($product);

        return ProductResponseDTO::create($product);
    }

    public function delete(int $productId): void
    {
        $product = $this->productRepository->getById($productId);

        $this->productRepository->removeAndCommit($product);
    }
}
