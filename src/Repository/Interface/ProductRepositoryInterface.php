<?php

declare(strict_types=1);

namespace App\Repository\Interface;

use App\Entity\Product;
use App\Exception\ProductNotFoundException;

interface ProductRepositoryInterface
{
    /**
     * @throws ProductNotFoundException
     */
    public function getById(int $productId): Product;

    public function saveAndCommit(Product $product): void;

    /**
     * @return list<Product>
     */
    public function findProducts(): array;

    public function removeAndCommit(Product $product): void;
}
