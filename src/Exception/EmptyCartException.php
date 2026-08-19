<?php

declare(strict_types=1);

namespace App\Exception;

final class EmptyCartException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('Cart Is Empty');
    }
}
