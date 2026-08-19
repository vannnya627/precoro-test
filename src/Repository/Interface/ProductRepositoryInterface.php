<?php

declare(strict_types=1);

namespace App\Repository\Interface;

use App\Entity\Product;

interface ProductRepositoryInterface
{
    public function findById(int $productId): ?Product;

    /**
     * @param array<string, mixed>       $criteria
     * @param array<string, string>|null $orderBy
     *
     * @return list<Product>
     */
    public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array;

    public function saveAndCommit(Product $product): void;

    /**
     * @return list<Product>
     */
    public function findAll(): array;

    public function removeAndCommit(Product $product): void;
}
