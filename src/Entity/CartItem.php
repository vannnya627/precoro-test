<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;

#[ORM\Entity]
final class CartItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public private(set) int $id;

    #[ORM\ManyToOne(targetEntity: Cart::class, inversedBy: 'cartItems')]
    #[ORM\JoinColumn(nullable: false)]
    public private(set) Cart $cart;

    #[ORM\ManyToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(nullable: false)]
    public private(set) Product $product;

    #[ORM\Column(type: 'integer')]
    public private(set) int $quantity = 1 {
        set(int $value) {
            if ($value < 1) {
                throw new InvalidArgumentException('Кількість не може бути менша 1');
            }
            $this->quantity = $value;
        }
    }

    private function __construct() {}

    public function addQuantity(int $quantity): void
    {
        $this->quantity += $quantity;
    }

    public static function create(Cart $cart, Product $product, int $quantity): static
    {
        $cartItem = new self();
        $cartItem->cart = $cart;
        $cartItem->product = $product;
        $cartItem->quantity = $quantity;

        return $cartItem;
    }
}
