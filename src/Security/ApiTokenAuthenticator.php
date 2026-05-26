<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\ApiToken;
use App\Repository\ApiTokenRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

final class ApiTokenAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface
{
    public function __construct(
        private readonly ApiTokenRepository $apiTokenRepository,
    ) {
    }

    public function supports(Request $request): ?bool
    {
        if ($request->getPathInfo() === '/v1/api/auth/login') {
            return false;
        }

        return str_starts_with($request->getPathInfo(), '/v1/api/');
    }

    public function authenticate(Request $request): SelfValidatingPassport
    {
        $authorizationHeader = $request->headers->get('Authorization');

        if ($authorizationHeader === null || !str_starts_with($authorizationHeader, 'Bearer ')) {
            throw new CustomUserMessageAuthenticationException('Missing bearer token');
        }

        $plainToken = trim(substr($authorizationHeader, 7));

        if ($plainToken === '') {
            throw new CustomUserMessageAuthenticationException('Missing bearer token');
        }

        $tokenHash = hash('sha256', $plainToken);

        return new SelfValidatingPassport(
            new UserBadge($tokenHash, function (string $tokenHash): UserInterface {
                /** @var ApiToken|null $apiToken */
                $apiToken = $this->apiTokenRepository->findOneBy(['tokenHash' => $tokenHash]);

                if ($apiToken === null) {
                    throw new CustomUserMessageAuthenticationException('Invalid bearer token');
                }

                if ($apiToken->getRevokedAt() !== null) {
                    throw new CustomUserMessageAuthenticationException('Token is revoked');
                }

                if ($apiToken->getExpiresAt() <= new \DateTimeImmutable()) {
                    throw new CustomUserMessageAuthenticationException('Token is expired');
                }

                return $apiToken->getUser();
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse([
            'success' => false,
            'error' => [
                'code' => 'authentication_failed',
                'message' => $exception->getMessageKey(),
            ],
        ], Response::HTTP_UNAUTHORIZED);
    }

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        return new JsonResponse([
            'success' => false,
            'error' => [
                'code' => 'authentication_required',
                'message' => 'Authentication is required',
            ],
        ], Response::HTTP_UNAUTHORIZED);
    }
}
