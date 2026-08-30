<?php

declare(strict_types=1);

namespace App\DTO\Response;

final readonly class CartItemResponseDTO
{
    public function __construct(
        public int $productId,
        public string $productName,
        public int $quantity,
    ) {}
}
