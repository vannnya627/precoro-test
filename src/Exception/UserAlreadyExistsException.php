<?php

declare(strict_types=1);

namespace App\Exception;

final class UserAlreadyExistsException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('User Already Exists');
    }
}
