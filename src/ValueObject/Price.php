<?php

declare(strict_types=1);

namespace App\ValueObject;

use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;

#[ORM\Embeddable]
final class Price
{
    private function __construct(
        #[ORM\Column(name: 'price', type: 'integer')]
        public private(set) int $value {
            set(int $value) {
                if ($value < 0) {
                    throw new InvalidArgumentException('Ціна не може бути менша 0');
                }
                $this->value = $value;
            }
        },
    ) {}

    public static function create(int $value): self
    {
        return new self($value);
    }
}
