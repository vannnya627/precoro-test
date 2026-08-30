<?php

declare(strict_types=1);

namespace App\DTO\Response;

final readonly class SignUpResponseDTO
{
    public function __construct(
        public int $userId,
        public string $email,
        public string $token,
    ) {}
}
