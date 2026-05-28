<?php

declare(strict_types=1);

namespace App\Controller\Api\V1;

use App\Dto\Auth\LoginRequestDto;
use App\Service\Auth\LoginService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/v1/api/auth')]
final class AuthController extends AbstractController
{
    #[Route('/login', name: 'api_v1_auth_login', methods: ['POST'])]
    public function login(
        #[MapRequestPayload(acceptFormat: 'json')] LoginRequestDto $dto,
        LoginService $loginService,
    ): JsonResponse
    {
        $apiToken = $loginService->login($dto->login, $dto->pass);

        return $this->json([
            'success' => true,
            'data' => [
                'token' => $apiToken->getTokenHash(),
                'token_type' => 'Bearer',
                'expires_at' => $apiToken->getExpiresAt()->format(DATE_ATOM),
            ],
        ]);
    }
}
