<?php

declare(strict_types=1);

namespace App\ValueObject;

use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;

#[ORM\Embeddable]
final class Quantity
{
    private function __construct(
        #[ORM\Column(name: 'quantity', type: 'integer')]
        public private(set) int $value {
            set(int $value) {
                if ($value < 1) {
                    throw new InvalidArgumentException('Кількість не може бути менша 1');
                }
                $this->value = $value;
            }
        },
    ) {}

    public static function create(int $value): self
    {
        return new self($value);
    }

    public function add(Quantity $other): self
    {
        return new self($this->value + $other->value);
    }
}
