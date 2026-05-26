<?php

declare(strict_types=1);

namespace App\Dto\Auth;

use Symfony\Component\Validator\Constraints as Assert;

final class LoginRequestDto
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 8)]
        public readonly string $login,
        #[Assert\NotBlank]
        #[Assert\Length(max: 8)]
        public readonly string $pass,
    ) {
    }
}