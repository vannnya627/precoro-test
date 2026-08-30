<?php

declare(strict_types=1);

namespace App\DTO\Error;

use OpenApi\Attributes as OA;

final readonly class ErrorResponseDTO
{
    /**
     * @param array<string, mixed>|null $context
     */
    public function __construct(
        public string $type,
        public int $status,
        public string $title,
        public string $detail,
        #[OA\Property(
            description: 'Додатковий контекст помилки бізнес-логіки',
            type: 'object',
            example: ['cart_id' => 99],
        )]
        public ?array $context = null,
        #[OA\Property(
            description: 'Трейс (Доступний тільки у debug=true)',
            example: '#0 someTrace....',
        )]
        public ?string $trace = null,
    ) {}
}
