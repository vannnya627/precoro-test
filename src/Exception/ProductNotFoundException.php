<?php

declare(strict_types=1);

namespace App\Exception;

final class ProductNotFoundException extends \RuntimeException implements ApiExceptionInterface
{
    public function __construct(private readonly int $productId)
    {
        parent::__construct('Продукт не знайдено');
    }

    public function getContext(): array
    {
        return ['productId' => $this->productId];
    }
}
