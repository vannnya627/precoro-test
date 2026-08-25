<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\Request\AddItemRequestDTO;
use App\DTO\Response\CartItemResponseDTO;
use App\Entity\CartItem;
use App\Entity\User;
use App\Exception\ProductNotFoundException;
use App\Repository\Interface\CartItemRepositoryInterface;
use App\Repository\Interface\CartRepositoryInterface;
use App\Repository\Interface\ProductRepositoryInterface;
use App\Service\Interface\CartServiceInterface;

final readonly class CartService implements CartServiceInterface
{
    public function __construct(
        private CartRepositoryInterface $cartRepository,
        private ProductRepositoryInterface $productRepository,
        private CartItemRepositoryInterface $cartItemRepository,
    ) {
    }

    /**
     * @throws \Throwable
     */
    public function addItem(AddItemRequestDTO $request, User $user): void
    {
        $product = $this->productRepository->findById($request->productId);

        if (null === $product) {
            throw new ProductNotFoundException();
        }

        $cart = $user->getCartOrCreate();

        $existingItem = $this->cartItemRepository->findByCartAndProduct($cart, $product);

        $quantity = $request->quantity;
        if (null !== $existingItem) {
            $existingItem->addQuantity($quantity);
        } else {
            $newItem = CartItem::create($cart, $product, $quantity);

            $cart->addCartItem($newItem);
        }

        $this->cartRepository->saveAndCommit($cart);
    }

    public function getList(User $user): array
    {
        return array_map(function (CartItem $item): CartItemResponseDTO {
            $product = $item->getProduct();

            return new CartItemResponseDTO(
                productId: $product->getId(),
                productName: $product->getName(),
                quantity: $item->getQuantity(),
            );
        }, $this->cartItemRepository->findWithProducts($user));
    }
}
