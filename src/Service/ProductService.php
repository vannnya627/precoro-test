<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\Request\ProductRequestDTO;
use App\DTO\Request\UpdateProductRequestDTO;
use App\DTO\Response\ProductResponseDTO;
use App\Entity\Product;
use App\Exception\ProductNotFoundException;
use App\Repository\Interface\ProductRepositoryInterface;
use App\Service\Interface\ProductServiceInterface;

final readonly class ProductService implements ProductServiceInterface
{
    public function __construct(
        private ProductRepositoryInterface $productRepository,
    ) {
    }

    public function getOne(int $productId): ProductResponseDTO
    {
        $product = $this->productRepository->findById($productId);
        if (null === $product) {
            throw new ProductNotFoundException();
        }

        return ProductResponseDTO::create($product);
    }

    /**
     * @throws \Throwable
     */
    public function create(ProductRequestDTO $request): ProductResponseDTO
    {
        $product = Product::create($request->name, $request->description, $request->price);
        $this->productRepository->saveAndCommit($product);

        return ProductResponseDTO::create($product);
    }

    /**
     * @return list<ProductResponseDTO>
     */
    public function getAll(): array
    {
        return array_map(function (Product $product) {
            return ProductResponseDTO::create($product);
        }, $this->productRepository->findProducts());
    }

    /**
     * @throws \Throwable
     */
    public function update(int $productId, UpdateProductRequestDTO $request): ProductResponseDTO
    {
        $product = $this->productRepository->findById($productId);

        if (null === $product) {
            throw new ProductNotFoundException();
        }
        if (null !== $request->name) {
            $product->changeName($request->name);
        }
        if (null !== $request->price) {
            $product->changePrice($request->price);
        }
        if (null !== $request->description) {
            $product->changeDescription($request->description);
        }

        $this->productRepository->saveAndCommit($product);

        return ProductResponseDTO::create($product);
    }

    /**
     * @throws \Throwable
     */
    public function delete(int $productId): void
    {
        $product = $this->productRepository->findById($productId);

        if (null === $product) {
            throw new ProductNotFoundException();
        }

        $this->productRepository->removeAndCommit($product);
    }
}
