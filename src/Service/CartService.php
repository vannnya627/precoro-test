<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\Request\AddItemRequestDTO;
use App\DTO\Response\CartItemResponseDTO;
use App\Entity\Cart;
use App\Entity\CartItem;
use App\Entity\User;
use App\Repository\Interface\CartRepositoryInterface;
use App\Repository\Interface\ProductRepositoryInterface;
use App\Service\Interface\CartServiceInterface;

final readonly class CartService implements CartServiceInterface
{
    public function __construct(
        private CartRepositoryInterface $cartRepository,
        private ProductRepositoryInterface $productRepository,
    ) {
    }

    public function addItem(AddItemRequestDTO $request, User $user): void
    {
        $product = $this->productRepository->getById($request->productId);

        $cart = $user->getCart();

        if (null === $cart) {
            $cart = Cart::create($user);
            $user->addCart($cart);
        }

        $cart->addItem($product, $request->quantity);

        $this->cartRepository->saveAndCommit($cart);
    }

    public function getList(User $user): array
    {
        $cart = $this->cartRepository->findCartWithItemsAndProducts($user);

        if (null === $cart || $cart->getCartItems()->isEmpty()) {
            return [];
        }

        return array_values(
            $cart->getCartItems()->map(function (CartItem $item): CartItemResponseDTO {
                $product = $item->getProduct();

                return new CartItemResponseDTO(
                    productId: $product->getId(),
                    productName: $product->getName(),
                    quantity: $item->getQuantity(),
                );
            })->toArray()
        );
    }
}
