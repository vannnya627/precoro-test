<?php

declare(strict_types=1);

namespace App\ValueObject;

use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;

#[ORM\Embeddable]
final class Email
{
    private function __construct(
        #[ORM\Column(name: 'email', length: 180)]
        public private(set) string $value {
            set(string $value) {
                if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    throw new InvalidArgumentException('Не валідний email');
                }
                $this->value = $value;
            }
        },
    ) {}

    public static function create(string $value): self
    {
        return new self($value);
    }
}
