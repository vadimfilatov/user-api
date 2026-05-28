<?php

declare(strict_types=1);

namespace App\Service\User;

use App\Dto\User\UpdateUserRequestDto;
use App\Entity\User;
use App\Exception\UserAlreadyExistsException;
use App\Repository\UserRepository;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class UpdateUserService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasher $userPasswordHasher,
    ) {
    }

    public function update(User $user, UpdateUserRequestDto $dto, bool $canAssignRootRole): User
    {
        $encryptedPassword = $this->userPasswordHasher->hash($dto->pass);

        $existingUser = $this->userRepository->findOneBy([
            'login' => $dto->login,
            'pass' => $encryptedPassword,
        ]);

        if ($existingUser instanceof User && $existingUser->getId() !== $user->getId()) {
            throw new UserAlreadyExistsException('User with same login and pass already exists');
        }

        $user->setLogin($dto->login);
        $user->setPhone($dto->phone);
        $user->setPass($encryptedPassword);

        if ($dto->roles !== null) {
            if (!$canAssignRootRole) {
                throw new AccessDeniedHttpException('Only root user can update roles');
            }

            $roles = array_values(array_unique($dto->roles));

            if ($roles === []) {
                $roles = [User::ROLE_USER];
            }

            $user->setRoles($roles);
        }

        $this->userRepository->save($user, true);

        return $user;
    }
}