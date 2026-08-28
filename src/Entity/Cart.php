<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\CartRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: CartRepository::class)]
final class Cart
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public private(set) int $id;

    #[ORM\OneToOne(inversedBy: 'cart', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    public private(set) User $user;

    #[ORM\Column]
    public private(set) ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    public private(set) ?\DateTimeImmutable $updatedAt = null;

    /**
     * @var Collection<int, CartItem>
     */
    #[ORM\OneToMany(
        targetEntity: CartItem::class,
        mappedBy: 'cart',
        cascade: ['persist', 'remove'],
        fetch: 'EXTRA_LAZY',
        orphanRemoval: true
    )]
    public private(set) Collection $cartItems;

    private function __construct()
    {
        $this->cartItems = new ArrayCollection();
    }

    public static function create(User $user): static
    {
        $cart = new self();
        $cart->user = $user;

        return $cart;
    }

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    private function addCartItem(CartItem $cartItem): void
    {
        if (!$this->cartItems->contains($cartItem)) {
            $this->cartItems->add($cartItem);
        }
    }

    public function removeCartItem(CartItem $cartItem): static
    {
        $this->cartItems->removeElement($cartItem);

        return $this;
    }

    private function getItemForProduct(Product $product): ?CartItem
    {
        foreach ($this->cartItems as $item) {
            if ($item->product === $product) {
                return $item;
            }
        }

        return null;
    }

    public function addItem(Product $product, int $quantity): static
    {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Кількість не може дорівнювати нулю');
        }

        $existingItem = $this->getItemForProduct($product);

        if (null !== $existingItem) {
            $existingItem->addQuantity($quantity);
        } else {
            $newItem = CartItem::create($this, $product, $quantity);

            $this->addCartItem($newItem);
        }

        return $this;
    }

    public function clear(): void
    {
        $this->cartItems->clear();
    }
}
