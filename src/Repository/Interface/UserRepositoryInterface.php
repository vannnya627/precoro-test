<?php

declare(strict_types=1);

namespace App\Repository\Interface;

use App\Entity\User;

interface UserRepositoryInterface
{
    public function existByEmail(string $email): bool;

    public function saveAndCommit(User $user): void;

    public function getByEmail(string $email): User;
}
