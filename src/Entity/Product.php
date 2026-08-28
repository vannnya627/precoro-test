<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: ProductRepository::class)]
final class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(type: Types::TEXT)]
    private string $description;

    #[ORM\Column]
    private int $price;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    private function __construct()
    {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getPrice(): int
    {
        return $this->price;
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

    public static function create(string $name, string $description, int $price): static
    {
        $product = new static();
        $product->setName($name);
        $product->setDescription($description);
        $product->setPrice($price);

        return $product;
    }

    private function setName(string $name): void
    {
        if (strlen($name) > 255) {
            throw new \InvalidArgumentException("Ім'я для товару має бути не більше 255 символів");
        }
        $this->name = $name;
    }

    private function setPrice(int $price): void
    {
        if ($price < 0) {
            throw new \InvalidArgumentException('Ціна не може бути менша 0');
        }
        $this->price = $price;
    }

    private function setDescription(string $description): void
    {
        if (strlen($description) > 5000) {
            throw new \InvalidArgumentException('Опис для товару має бути не більше 5000 символів');
        }
        $this->description = $description;
    }

    public function update(?string $newName, ?string $newDescription, ?int $newPrice): void
    {
        if (null !== $newName) {
            $this->setName($newName);
        }

        if (null !== $newPrice) {
            $this->setPrice($newPrice);
        }

        if (null !== $newDescription) {
            $this->setDescription($newDescription);
        }
    }
}
