<?php

declare(strict_types=1);

namespace App\Repository\Interface;

use App\Entity\Cart;

interface CartRepositoryInterface
{
    public function findByUserId(int $userId): ?Cart;

    public function saveAndCommit(Cart $cart): void;
}
