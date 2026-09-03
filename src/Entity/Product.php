<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ProductRepository;
use App\ValueObject\Price;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use DateTimeImmutable;
use InvalidArgumentException;

#[ORM\HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: ProductRepository::class)]
final class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public private(set) int $id;

    #[ORM\Column(length: 255)]
    public private(set) string $name {
        set(string $value) {
            if (strlen($value) > 255) {
                throw new InvalidArgumentException("Ім'я для товару має бути не більше 255 символів");
            }
            $this->name = $value;
        }
    }

    #[ORM\Column(type: Types::TEXT)]
    public private(set) string $description {
        set(string $value) {
            if (strlen($value) > 5000) {
                throw new InvalidArgumentException('Опис для товару має бути не більше 5000 символів');
            }
            $this->description = $value;
        }
    }

    #[ORM\Embedded(class: Price::class, columnPrefix: false)]
    public private(set) Price $price;

    #[ORM\Column]
    public private(set) ?DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    public private(set) ?DateTimeImmutable $updatedAt = null;

    private function __construct() {}

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

    public static function create(string $name, string $description, Price $price): static
    {
        $product = new self();
        $product->name = $name;
        $product->description = $description;
        $product->price = $price;

        return $product;
    }

    public function update(?string $newName, ?string $newDescription, ?Price $newPrice): void
    {
        if (null !== $newName) {
            $this->name = $newName;
        }

        if (null !== $newPrice) {
            $this->price = $newPrice;
        }

        if (null !== $newDescription) {
            $this->description = $newDescription;
        }
    }
}
