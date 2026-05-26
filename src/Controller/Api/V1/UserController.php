<?php

declare(strict_types=1);

namespace App\Controller\Api\V1;

use App\Dto\User\CreateUserRequestDto;
use App\Entity\User;
use App\Service\User\CreateUserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/v1/api/users')]
final class UserController extends AbstractController
{
    #[Route('', name: 'api_v1_users_create', methods: ['POST'])]
    public function create(
        #[MapRequestPayload(acceptFormat: 'json')] CreateUserRequestDto $dto,
        CreateUserService $createUserService,
    ): JsonResponse {
        $canCreateUsers = $this->isGranted(User::ROLE_ROOT) || $this->isGranted(User::ROLE_USER);
        if (!$canCreateUsers) {
            throw new AccessDeniedHttpException('You do not have permission to create users');
        }

        $user = $createUserService->create($dto, $this->isGranted('ROLE_ROOT'));

        return $this->json([
            'success' => true,
            'data' => [
                'id' => $user->getId(),
                'login' => $user->getLogin(),
                'phone' => $user->getPhone(),
                'pass' => $dto->pass,
            ],
        ], Response::HTTP_CREATED);
    }
}
