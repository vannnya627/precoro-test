<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\OrderStatus;
use App\Repository\OrderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use DateTimeImmutable;
use InvalidArgumentException;

#[ORM\HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: '`order`')]
final class Order
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public private(set) int $id;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'orders')]
    #[ORM\JoinColumn(nullable: false)]
    public private(set) ?User $user = null;

    #[ORM\Column(nullable: false)]
    public private(set) int $totalPrice {
        set(int $value) {
            if ($value < 0) {
                throw new InvalidArgumentException("Загальна вартість не може бути від'ємною");
            }
            $this->totalPrice = $value;
        }
    }

    #[ORM\Column(enumType: OrderStatus::class)]
    public private(set) OrderStatus $status = OrderStatus::NEW;

    #[ORM\Column]
    public private(set) ?DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    public private(set) ?DateTimeImmutable $updatedAt = null;

    /**
     * @var Collection<int, OrderItem>
     */
    #[ORM\OneToMany(
        targetEntity: OrderItem::class,
        mappedBy: 'order',
        cascade: ['persist', 'remove'],
        orphanRemoval: true,
    )]
    public private(set) Collection $orderItems;

    private function __construct()
    {
        $this->orderItems = new ArrayCollection();
    }

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }

    public function removeOrderItem(OrderItem $orderItem): static
    {
        $this->orderItems->removeElement($orderItem);

        return $this;
    }

    private function recalculateTotalPrice(): void
    {
        $total = 0;
        foreach ($this->orderItems as $item) {
            $total += $item->price * $item->quantity;
        }
        $this->totalPrice = $total;
    }

    public static function create(User $user): static
    {
        $order = new self();
        $order->user = $user;

        return $order;
    }

    /**
     * @param Collection<int, CartItem> $cartItems
     */
    public function addItemsFromCart(Collection $cartItems): void
    {
        foreach ($cartItems as $cartItem) {
            $orderItem = OrderItem::create($this, $cartItem);

            $this->orderItems->add($orderItem);
        }

        $this->recalculateTotalPrice();
    }
}
