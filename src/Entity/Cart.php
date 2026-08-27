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
    private int $id;

    #[ORM\OneToOne(inversedBy: 'cart', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

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
    private Collection $cartItems;

    public function __construct(User $user)
    {
        $this->user = $user;
        $this->cartItems = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
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

    /**
     * @return Collection<int, CartItem>
     */
    public function getCartItems(): Collection
    {
        return $this->cartItems;
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
            if ($item->getProduct() === $product) {
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
