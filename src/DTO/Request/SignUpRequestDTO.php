<?php

declare(strict_types=1);

namespace App\DTO\Request;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class SignUpRequestDTO
{
    public function __construct(
        #[OA\Property(description: 'Пошта користувача', example: 'user@gmail.com')]
        #[Assert\NotBlank(message: 'Email не може бути порожнім.')]
        #[Assert\Email]
        public string $email,

        #[OA\Property(description: 'Пароль користувача', example: '1234567890')]
        #[Assert\NotBlank(message: 'Пароль не може бути порожнім.')]
        #[Assert\Length(
            min: 8,
            max: 64,
            minMessage: 'Пароль має містити щонайменше {{ limit }} символів.',
            maxMessage: 'Пароль надто довгий.'
        )]
        public string $password,
    ) {
    }
}
