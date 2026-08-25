<?php

declare(strict_types=1);

namespace App\Repository\Interface;

use App\Entity\Order;

interface OrderRepositoryInterface
{
    /**
     * @return list<Order>
     */
    public function findAllByUserIdWithProduct(int $userId): array;

    public function save(Order $order): void;

    public function commit(): void;
}
