<?php

declare(strict_types=1);

namespace App\DTO\Request;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class AddItemRequestDTO
{
    public function __construct(
        #[OA\Property(description: 'Id продукту', example: 1)]
        #[Assert\NotBlank(message: "Поле 'productId' не може бути порожнє")]
        #[Assert\Positive(message: "Поле 'productId' не може бути менше нуля або дорівнювати нулю")]
        #[Assert\Type('int', message: 'Id має бути цілим числом')]
        public int $productId,
        #[OA\Property(description: 'Кількість одиниць товару', example: 5)]
        #[Assert\NotBlank(message: "Поле 'quantity' не може бути порожнє")]
        #[Assert\Positive(message: "Поле 'quantity' не може бути менше нуля або дорівнювати нулю")]
        #[Assert\Type('int', message: 'Кількість одиниць товару має бути цілим числом')]
        public int $quantity,
    ) {
    }
}
