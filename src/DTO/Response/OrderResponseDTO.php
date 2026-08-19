<?php

declare(strict_types=1);

namespace App\DTO\Response;

final readonly class OrderResponseDTO
{
    /**
     * @param list<OrderItemResponseDTO> $orderItems
     */
    public function __construct(
        public int $id,
        public int $totalPrice,
        public string $status,
        public array $orderItems,
    ) {
    }
}
