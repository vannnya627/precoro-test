<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\Request\AddItemRequestDTO;
use App\DTO\Response\CartItemResponseDTO;
use App\Entity\Cart;
use App\Entity\CartItem;
use App\Repository\Interface\CartRepositoryInterface;
use App\Repository\Interface\ProductRepositoryInterface;
use App\Repository\Interface\UserRepositoryInterface;
use App\ValueObject\Email;
use App\ValueObject\Quantity;

final readonly class CartService
{
    public function __construct(
        private CartRepositoryInterface $cartRepository,
        private ProductRepositoryInterface $productRepository,
        private UserRepositoryInterface $userRepository,
    ) {}

    public function addItem(AddItemRequestDTO $request, Email $email): void
    {
        $user = $this->userRepository->getByEmail($email);
        $product = $this->productRepository->getById($request->productId);

        $cart = $user->cart;

        if (null === $cart) {
            $cart = Cart::create($user);
            $user->addCart($cart);
        }

        $quantity = Quantity::create($request->quantity);
        $cart->addItem($product, $quantity);

        $this->cartRepository->saveAndCommit($cart);
    }

    /**
     * @return list<CartItemResponseDTO>
     */
    public function getList(Email $email): array
    {
        $user = $this->userRepository->getByEmail($email);
        $cart = $this->cartRepository->findCartWithItemsAndProducts($user);

        if (null === $cart || $cart->cartItems->isEmpty()) {
            return [];
        }

        return array_values(
            $cart->cartItems->map(function (CartItem $item): CartItemResponseDTO {
                $product = $item->product;

                return new CartItemResponseDTO(
                    productId: $product->id,
                    productName: $product->name,
                    quantity: $item->quantity->value,
                );
            })->toArray(),
        );
    }
}
