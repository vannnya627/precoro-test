<?php

declare(strict_types=1);

namespace App\Exception;

interface ApiExceptionInterface
{
    /**
     * @return array<string, mixed>
     */
    public function getContext(): array;
}
