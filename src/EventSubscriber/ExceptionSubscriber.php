<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Exception\InvalidCredentialsException;
use App\Exception\UserAlreadyExistsException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;

final class ExceptionSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            ExceptionEvent::class => 'onKernelException',
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $throwable = $event->getThrowable();

        if ($throwable instanceof UserAlreadyExistsException) {
            $event->setResponse($this->createErrorResponse(
                'user_already_exists',
                $throwable->getMessage(),
                Response::HTTP_CONFLICT,
            ));

            return;
        }

        if ($throwable instanceof InvalidCredentialsException) {
            $event->setResponse($this->createErrorResponse(
                'invalid_credentials',
                $throwable->getMessage(),
                Response::HTTP_UNAUTHORIZED,
            ));

            return;
        }

        if ($throwable instanceof UnprocessableEntityHttpException) {
            $event->setResponse($this->createErrorResponse(
                'validation_error',
                'Validation failed.',
                Response::HTTP_UNPROCESSABLE_ENTITY,
                $this->extractValidationErrors($throwable),
            ));

            return;
        }

        if ($throwable instanceof BadRequestHttpException) {
            $event->setResponse($this->createErrorResponse(
                'bad_request',
                $throwable->getMessage(),
                Response::HTTP_BAD_REQUEST,
            ));

            return;
        }

        if ($throwable instanceof NotFoundHttpException) {
            $event->setResponse($this->createErrorResponse(
                'not_found',
                $throwable->getMessage() !== '' ? $throwable->getMessage() : 'Resource not found.',
                Response::HTTP_NOT_FOUND,
            ));

            return;
        }

        if ($throwable instanceof AuthenticationException) {
            $event->setResponse($this->createErrorResponse(
                'authentication_failed',
                $throwable->getMessageKey(),
                Response::HTTP_UNAUTHORIZED,
            ));

            return;
        }

        if ($throwable instanceof AccessDeniedException) {
            $event->setResponse($this->createErrorResponse(
                'access_denied',
                $throwable->getMessage() !== '' ? $throwable->getMessage() : 'Access denied.',
                Response::HTTP_FORBIDDEN,
            ));

            return;
        }

        if ($throwable instanceof HttpExceptionInterface) {
            $event->setResponse($this->createErrorResponse(
                'http_error',
                $throwable->getMessage() !== '' ? $throwable->getMessage() : Response::$statusTexts[$throwable->getStatusCode()],
                $throwable->getStatusCode(),
                null,
                $throwable->getHeaders(),
            ));

            return;
        }

        $event->setResponse($this->createErrorResponse(
            'internal_error',
            'Internal server error.',
            Response::HTTP_INTERNAL_SERVER_ERROR,
        ));
    }

    private function createErrorResponse(
        string $code,
        string $message,
        int $status,
        ?array $details = null,
        array $headers = [],
    ): JsonResponse {
        $payload = [
            'success' => false,
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ];

        if ($details !== null && $details !== []) {
            $payload['error']['details'] = $details;
        }

        return new JsonResponse($payload, $status, $headers);
    }

    private function extractValidationErrors(UnprocessableEntityHttpException $exception): array
    {
        $previous = $exception->getPrevious();

        if (!$previous instanceof ValidationFailedException) {
            return [];
        }

        $errors = [];

        /** @var ConstraintViolationInterface $violation */
        foreach ($previous->getViolations() as $violation) {
            $propertyPath = $violation->getPropertyPath();
            $errors[$propertyPath][] = $violation->getMessage();
        }

        return $errors;
    }
}
