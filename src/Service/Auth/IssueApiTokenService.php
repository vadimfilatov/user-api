<?php

declare(strict_types=1);

namespace App\Service\Auth;

use App\Entity\ApiToken;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

final class IssueApiTokenService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function issue(User $user): array
    {
        $plainToken = bin2hex(random_bytes(32));
        $expiresAt = new \DateTimeImmutable('+7 day');

        $apiToken = new ApiToken();
        $apiToken->setUser($user);
        $apiToken->setTokenHash(hash('sha256', $plainToken));
        $apiToken->setExpiresAt($expiresAt);

        $this->entityManager->persist($apiToken);
        $this->entityManager->flush();

        return [
            'token' => $plainToken,
            'expiresAt' => $expiresAt,
        ];
    }
}
