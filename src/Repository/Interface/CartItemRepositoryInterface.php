<?php

declare(strict_types=1);

namespace App\Repository\Interface;

use App\Entity\Cart;
use App\Entity\CartItem;
use App\Entity\Product;
use App\Entity\User;

interface CartItemRepositoryInterface
{
    /**
     * @return list<CartItem>
     */
    public function findWithProducts(User $user): array;

    public function remove(CartItem $cartItem): void;

    public function findByCartAndProduct(Cart $cart, Product $product): ?CartItem;
}
