<?php

declare(strict_types=1);

namespace App\Controller\Api\V1;

use App\Dto\Auth\LoginRequestDto;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use App\Exception\InvalidCredentialsException;
use App\Service\Auth\LoginService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
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
        try {
            $result = $loginService->login($dto->login, $dto->pass);
        } catch (InvalidCredentialsException $exception) {
            return $this->json([
                'success' => false,
                'error' => [
                    'code' => 'invalid_credentials',
                    'message' => $exception->getMessage(),
                ],
            ], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json([
            'success' => true,
            'data' => [
                'token' => $result['token'],
                'token_type' => 'Bearer',
                'expires_at' => $result['expiresAt']->format(DATE_ATOM),
            ],
        ]);
    }
}
