<?php

declare(strict_types=1);

namespace App\Exception;

use RuntimeException;

class UserNotFoundException extends RuntimeException implements ApiExceptionInterface
{
    public function __construct(private readonly string $email)
    {
        parent::__construct('Користувача не знайдено');
    }

    public function getContext(): array
    {
        return ['email' => $this->email];
    }
}
