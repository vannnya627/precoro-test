<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\Request\AddItemRequestDTO;
use App\DTO\Response\CartItemResponseDTO;
use App\Entity\Cart;
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

        $cart = $user->getCart();
        if (null === $cart) {
            $cart = new Cart();
            $user->setCart($cart);
        }

        /** @var Cart $cart */
        $existingItem = $this->cartItemRepository->findOneBy([
            'cart' => $cart,
            'product' => $product,
        ]);

        if (null !== $existingItem) {
            $existingItem->setQuantity($existingItem->getQuantity() + $request->quantity);
        } else {
            $newItem = new CartItem()
                ->setCart($cart)
                ->setProduct($product)
                ->setQuantity($request->quantity);

            $cart->addCartItem($newItem);
        }

        $this->cartRepository->saveAndCommit($cart);
    }

    public function getList(User $user): array
    {
        /** @var list<CartItem> $cartItems */
        $cartItems = $user->getCart()?->getCartItems()->toArray();

        if (empty($cartItems)) {
            return [];
        }

        $productIds = [];
        foreach ($cartItems as $item) {
            $productIds[] = $item->getProduct()->getId();
        }

        $this->productRepository->findBy(['id' => $productIds]);

        return array_map(function (CartItem $item): CartItemResponseDTO {
            $product = $item->getProduct();

            return new CartItemResponseDTO(
                productId: $product->getId(),
                productName: $product->getName(),
                quantity: $item->getQuantity(),
            );
        }, $cartItems);
    }
}
