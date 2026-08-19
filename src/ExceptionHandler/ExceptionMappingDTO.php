<?php

declare(strict_types=1);

namespace App\ExceptionHandler;

final readonly class ExceptionMappingDTO
{
    public function __construct(
        private string $type,
        private int $code,
        private bool $loggable,
    ) {
    }

    public static function fromTypeAndCode(string $type, int $code): self
    {
        return new self($type, $code, true);
    }

    public function getCode(): int
    {
        return $this->code;
    }

    public function isLoggable(): bool
    {
        return $this->loggable;
    }

    public function getType(): string
    {
        return $this->type;
    }
}
