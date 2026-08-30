<?php

declare(strict_types=1);

namespace App\Entity;

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

    #[ORM\Column(type: 'integer')]
    public private(set) int $quantity = 1 {
        set(int $value) {
            if ($value < 1) {
                throw new \InvalidArgumentException('Кількість не може бути менша 1');
            }
            $this->quantity = $value;
        }
    }

    #[ORM\Column(type: 'integer')]
    public private(set) int $price {
        set(int $value) {
            if ($value < 0) {
                throw new \InvalidArgumentException('Ціна не може бути менша 0');
            }
            $this->price = $value;
        }
    }

    private function __construct()
    {
    }

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
