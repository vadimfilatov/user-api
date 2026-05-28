<?php

declare(strict_types=1);

namespace App\Service\Auth;

use App\Entity\ApiToken;
use App\Entity\User;
use App\Exception\InvalidCredentialsException;
use App\Repository\ApiTokenRepository;
use App\Repository\UserRepository;
use App\Service\User\UserPasswordHasher;

final class LoginService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasher $userPasswordHasher,
        private readonly IssueApiTokenService $issueApiTokenService,
        private readonly ApiTokenRepository $apiTokenRepository,
    ) {
    }

    public function login(string $login, string $plainPassword): ApiToken
    {
        /** @var User $user */
        $user = $this->userRepository->findOneBy(['login' => $login]);

        if (!$user || !$this->userPasswordHasher->verify($plainPassword, $user->getPassword())) {
            throw new InvalidCredentialsException('Invalid login or password');
        }

        /** @var ApiToken|null $apiToken */
        $apiToken = $this->apiTokenRepository->findOneBy(['user' => $user]);

        return $apiToken ?? $this->issueApiTokenService->issue($user);
    }
}
