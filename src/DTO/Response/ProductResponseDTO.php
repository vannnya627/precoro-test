<?php

declare(strict_types=1);

namespace App\DTO\Response;

use App\Entity\Product;

final class ProductResponseDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public string $description,
        public int $price,
    ) {}

    public static function create(Product $product): self
    {
        return new self(
            id: $product->id,
            name: $product->name,
            description: $product->description,
            price: $product->price,
        );
    }
}
