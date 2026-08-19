<?php

declare(strict_types=1);

namespace App\Service\Interface;

use App\DTO\Request\AddItemRequestDTO;
use App\DTO\Response\CartItemResponseDTO;
use App\Entity\User;

interface CartServiceInterface
{
    public function addItem(AddItemRequestDTO $request, User $user): void;

    /**
     * @return list<CartItemResponseDTO>
     */
    public function getList(User $user): array;
}
