<?php

declare(strict_types=1);

namespace App\Entity;

use App\ValueObject\Quantity;
use Doctrine\ORM\Mapping as ORM;

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

    #[ORM\Embedded(class: Quantity::class, columnPrefix: false)]
    public private(set) Quantity $quantity;

    private function __construct() {}

    public function addQuantity(Quantity $newQuantity): void
    {
        $this->quantity = $this->quantity->add($newQuantity);
    }

    public static function create(Cart $cart, Product $product, Quantity $quantity): static
    {
        $cartItem = new self();
        $cartItem->cart = $cart;
        $cartItem->product = $product;
        $cartItem->quantity = $quantity;

        return $cartItem;
    }
}
