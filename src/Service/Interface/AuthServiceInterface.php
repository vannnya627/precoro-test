<?php

declare(strict_types=1);

namespace App\Service\Interface;

use App\DTO\Request\SignUpRequestDTO;
use App\DTO\Response\SignUpResponseDTO;

interface AuthServiceInterface
{
    public function signUp(SignUpRequestDTO $request): SignUpResponseDTO;
}
