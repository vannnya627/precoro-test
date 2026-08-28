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
    private int $id;

    #[ORM\ManyToOne(targetEntity: Order::class, inversedBy: 'orderItems')]
    #[ORM\JoinColumn(nullable: false)]
    private Order $order;

    #[ORM\ManyToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Product $product;

    #[ORM\Column(type: 'integer')]
    private int $quantity = 1;

    #[ORM\Column(type: 'integer')]
    private int $price;

    private function __construct()
    {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getOrder(): Order
    {
        return $this->order;
    }

    public function getProduct(): Product
    {
        return $this->product;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function getPrice(): int
    {
        return $this->price;
    }

    public static function create(Order $order, CartItem $cartItem): static
    {
        $orderItem = new static();
        $orderItem->order = $order;
        $orderItem->product = $cartItem->getProduct();
        $orderItem->setQuantity($cartItem->getQuantity());
        $orderItem->setPrice($cartItem->getProduct()->getPrice());

        return $orderItem;
    }

    private function setPrice(int $price): void
    {
        if ($price < 0) {
            throw new \InvalidArgumentException('Ціна не може бути менша 0');
        }
        $this->price = $price;
    }

    private function setQuantity(int $quantity): void
    {
        if ($quantity < 1) {
            throw new \InvalidArgumentException('Кількість не може бути менша 1');
        }
        $this->quantity = $quantity;
    }
}
