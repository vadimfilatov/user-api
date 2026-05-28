<?php

declare(strict_types=1);

namespace App\Service\User;

use App\Entity\User;

final class UserResponseMapper
{
    public function __construct(
        private readonly UserPasswordHasher $userPasswordHasher,
    ) {
    }

    public function map(User $user): array
    {
        return [
            'id' => $user->getId(),
            'login' => $user->getLogin(),
            'phone' => $user->getPhone(),
            'pass' => $this->userPasswordHasher->decrypt($user->getPass()),
        ];
    }
}
