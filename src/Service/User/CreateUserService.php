<?php

declare(strict_types=1);

namespace App\Service\User;

use App\Dto\User\CreateUserRequestDto;
use App\Entity\User;
use App\Exception\UserAlreadyExistsException;
use App\Repository\UserRepository;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class CreateUserService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasher $userPasswordHasher,
    ) {
    }

    public function create(CreateUserRequestDto $dto, bool $canAssignRootRole): User
    {
        $hashedPassword = $this->userPasswordHasher->hash($dto->pass);

        if ($this->userRepository->findOneBy(['login' => $dto->login, 'pass' => $hashedPassword]) !== null) {
            throw new UserAlreadyExistsException('User with same login and pass already exists');
        }

        $roles = $dto->roles ?? [User::ROLE_USER];
        $roles = array_values(array_unique($roles));

        if (!$canAssignRootRole && in_array(User::ROLE_ROOT, $roles, true)) {
            throw new AccessDeniedHttpException('Only root user can assign ROLE_ROOT');
        }

        if ($roles === []) {
            $roles = [User::ROLE_USER];
        }

        $user = new User();
        $user->setLogin($dto->login);
        $user->setPhone($dto->phone);
        $user->setPass($hashedPassword);
        $user->setRoles($roles);

        $this->userRepository->save($user, true);

        return $user;
    }
}
