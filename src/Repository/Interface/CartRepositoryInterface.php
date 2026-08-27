<?php

declare(strict_types=1);

namespace App\Repository\Interface;

use App\Entity\Cart;
use App\Entity\User;

interface CartRepositoryInterface
{
    public function findByUserId(int $userId): ?Cart;

    public function saveAndCommit(Cart $cart): void;

    public function findCartWithItemsAndProducts(User $user): ?Cart;

    public function save(Cart $cart): void;
}
