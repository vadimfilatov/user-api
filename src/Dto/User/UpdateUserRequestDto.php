<?php

declare(strict_types=1);

namespace App\Dto\User;

use App\Entity\User;
use Symfony\Component\Validator\Constraints as Assert;

final class UpdateUserRequestDto
{
    /**
     * @param list<string>|null $roles
     */
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 8)]
        public readonly string $login,
        #[Assert\NotBlank]
        #[Assert\Length(max: 8)]
        public readonly string $phone,
        #[Assert\NotBlank]
        #[Assert\Length(max: 8)]
        public readonly string $pass,
        #[Assert\All([
            new Assert\Choice(choices: User::ROLES_LIST),
        ])]
        public readonly ?array $roles = null,
    ) {
    }
}