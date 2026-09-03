<?php

declare(strict_types=1);

namespace App\Entity;

use App\ValueObject\Price;
use App\ValueObject\Quantity;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
final class OrderItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public private(set) int $id;

    #[ORM\ManyToOne(targetEntity: Order::class, inversedBy: 'orderItems')]
    #[ORM\JoinColumn(nullable: false)]
    public private(set) Order $order;

    #[ORM\ManyToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(nullable: false)]
    public private(set) Product $product;

    #[ORM\Embedded(class: Quantity::class, columnPrefix: false)]
    public private(set) Quantity $quantity;

    #[ORM\Embedded(class: Price::class, columnPrefix: false)]
    public private(set) Price $price;

    private function __construct() {}

    public static function create(Order $order, CartItem $cartItem): static
    {
        $orderItem = new self();
        $orderItem->order = $order;
        $orderItem->product = $cartItem->product;
        $orderItem->quantity = $cartItem->quantity;
        $orderItem->price = $cartItem->product->price;

        return $orderItem;
    }
}
