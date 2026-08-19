<?php

declare(strict_types=1);

namespace App\Service\Interface;

use App\DTO\Response\OrderResponseDTO;
use App\Entity\User;

interface OrderServiceInterface
{
    public function create(User $user): OrderResponseDTO;

    /**
     * @return list<OrderResponseDTO>
     */
    public function getList(User $user): array;
}
