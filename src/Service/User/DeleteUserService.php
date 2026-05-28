<?php

declare(strict_types=1);

namespace App\Service\User;

use App\Entity\User;
use App\Repository\UserRepository;

final class DeleteUserService
{
    public function __construct(
        private readonly UserRepository $userRepository,
    ) {
    }

    public function delete(User $user): void
    {
        $this->userRepository->remove($user, true);
    }
}