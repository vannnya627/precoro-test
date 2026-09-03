<?php

declare(strict_types=1);

namespace App\Repository\Interface;

use App\Entity\User;
use App\ValueObject\Email;

interface UserRepositoryInterface
{
    public function existByEmail(Email $email): bool;

    public function saveAndCommit(User $user): void;

    public function getByEmail(Email $email): User;
}
