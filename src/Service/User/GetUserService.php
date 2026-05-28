<?php

declare(strict_types=1);

namespace App\Service\User;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class GetUserService
{
    public function __construct(
        private readonly UserRepository $userRepository,
    ) {
    }

    public function getById(int $id): User
    {
        $user = $this->userRepository->find($id);

        if (!$user instanceof User) {
            throw new NotFoundHttpException('User not found.');
        }

        return $user;
    }
}