<?php

declare(strict_types=1);

namespace App\Repository\Interface;

use App\Entity\Product;

interface ProductRepositoryInterface
{
    public function findById(int $productId): ?Product;

    public function saveAndCommit(Product $product): void;

    /**
     * @return list<Product>
     */
    public function findProducts(): array;

    public function removeAndCommit(Product $product): void;
}
