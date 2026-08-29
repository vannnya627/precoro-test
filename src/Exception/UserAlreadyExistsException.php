<?php

declare(strict_types=1);

namespace App\Exception;

final class UserAlreadyExistsException extends \RuntimeException implements ApiExceptionInterface
{
    public function __construct(private readonly string $email)
    {
        parent::__construct('Пошта вже використовується');
    }

    public function getContext(): array
    {
        return ['email' => $this->email];
    }
}
