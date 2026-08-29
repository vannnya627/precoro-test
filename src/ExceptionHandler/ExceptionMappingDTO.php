<?php

declare(strict_types=1);

namespace App\ExceptionHandler;

final readonly class ExceptionMappingDTO
{
    public function __construct(
        public private(set) string $type,
        public private(set) int $code,
        public private(set) bool $loggable,
    ) {
    }

    public static function fromTypeAndCode(string $type, int $code): self
    {
        return new self($type, $code, true);
    }
}
