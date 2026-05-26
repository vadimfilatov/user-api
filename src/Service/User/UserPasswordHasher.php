<?php

declare(strict_types=1);

namespace App\Service\User;

final class UserPasswordHasher
{
    public function __construct(
        private readonly string $secret,
    ) {
    }

    public function hash(string $plainPassword): string
    {
        return hash_hmac('sha256', $plainPassword, $this->secret);
    }

    public function verify(string $plainPassword, string $hashedPassword): bool
    {
        return hash_equals($hashedPassword, $this->hash($plainPassword));
    }
}
