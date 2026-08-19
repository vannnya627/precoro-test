<?php

declare(strict_types=1);

namespace App\Service\Interface;

use App\DTO\Request\ProductRequestDTO;
use App\DTO\Request\UpdateProductRequestDTO;
use App\DTO\Response\ProductResponseDTO;

interface ProductServiceInterface
{
    public function getOne(int $productId): ProductResponseDTO;

    public function create(ProductRequestDTO $request): ProductResponseDTO;

    /**
     * @return list<ProductResponseDTO>
     */
    public function getAll(): array;

    public function update(int $productId, UpdateProductRequestDTO $request): ProductResponseDTO;

    public function delete(int $productId): void;
}
