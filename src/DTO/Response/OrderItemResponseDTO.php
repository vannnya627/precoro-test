<?php

declare(strict_types=1);

namespace App\DTO\Response;

use App\Entity\OrderItem;

final readonly class OrderItemResponseDTO
{
    public function __construct(
        public int $productId,
        public string $productName,
        public int $quantity,
        public int $price,
    ) {
    }

    public static function create(OrderItem $orderItem): self
    {
        return new self(
            productId: $orderItem->product->id,
            productName: $orderItem->product->name,
            quantity: $orderItem->quantity,
            price: $orderItem->price,
        );
    }
}
