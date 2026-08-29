<?php

declare(strict_types=1);

namespace App\Exception;

final class EmptyCartException extends \RuntimeException implements ApiExceptionInterface
{
    public function __construct(private readonly ?int $cartId)
    {
        parent::__construct('Кошик порожній');
    }

    public function getContext(): array
    {
        return ['cartId' => $this->cartId];
    }
}
