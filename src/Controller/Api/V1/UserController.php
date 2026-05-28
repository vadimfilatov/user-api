<?php

declare(strict_types=1);

namespace App\Controller\Api\V1;

use App\Dto\User\CreateUserRequestDto;
use App\Dto\User\UpdateUserRequestDto;
use App\Entity\User;
use App\Security\Voter\UserVoter;
use App\Service\User\CreateUserService;
use App\Service\User\DeleteUserService;
use App\Service\User\GetUserService;
use App\Service\User\UserResponseMapper;
use App\Service\User\UpdateUserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/v1/api/users')]
final class UserController extends AbstractController
{
    #[Route('/{id}', name: 'api_v1_users_get', requirements: ['id' => '\\d+'], methods: ['GET'])]
    public function get(int $id, GetUserService $getUserService, UserResponseMapper $userResponseMapper): JsonResponse
    {
        $user = $getUserService->getById($id);
        $this->denyAccessUnlessGranted(UserVoter::VIEW, $user);

        return $this->json([
            'success' => true,
            'data' => $userResponseMapper->map($user),
        ]);
    }

    #[Route('', name: 'api_v1_users_create', methods: ['POST'])]
    public function create(
        #[MapRequestPayload(acceptFormat: 'json')] CreateUserRequestDto $dto,
        CreateUserService $createUserService,
        UserResponseMapper $userResponseMapper,
    ): JsonResponse {
        $this->denyAccessUnlessGranted(UserVoter::CREATE);

        $user = $createUserService->create($dto, $this->isGranted('ROLE_ROOT'));

        return $this->json([
            'success' => true,
            'data' => $userResponseMapper->map($user),
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_v1_users_update', requirements: ['id' => '\\d+'], methods: ['PUT'])]
    public function update(
        int $id,
        #[MapRequestPayload(acceptFormat: 'json')] UpdateUserRequestDto $dto,
        GetUserService $getUserService,
        UpdateUserService $updateUserService,
        UserResponseMapper $userResponseMapper,
    ): JsonResponse {
        $user = $getUserService->getById($id);
        $this->denyAccessUnlessGranted(UserVoter::EDIT, $user);

        $updatedUser = $updateUserService->update($user, $dto, $this->isGranted(User::ROLE_ROOT));

        return $this->json([
            'success' => true,
            'data' => $userResponseMapper->map($updatedUser),
        ]);
    }

    #[Route('/{id}', name: 'api_v1_users_delete', requirements: ['id' => '\\d+'], methods: ['DELETE'])]
    public function delete(
        int $id,
        GetUserService $getUserService,
        DeleteUserService $deleteUserService,
    ): JsonResponse {
        $user = $getUserService->getById($id);
        $this->denyAccessUnlessGranted(UserVoter::DELETE, $user);
        $deleteUserService->delete($user);

        return $this->json([
            'success' => true,
            'data' => [
                'id' => $id,
            ],
        ]);
    }
}
