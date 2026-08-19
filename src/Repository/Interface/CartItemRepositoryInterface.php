<?php

declare(strict_types=1);

namespace App\Repository\Interface;

use App\Entity\CartItem;

interface CartItemRepositoryInterface
{
    /**
     * @param array<string, mixed>       $criteria
     * @param array<string, string>|null $orderBy
     *
     * @return CartItem|null
     */
    public function findOneBy(array $criteria, ?array $orderBy = null): ?object;
}
