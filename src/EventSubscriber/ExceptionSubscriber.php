<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Exception\UserAlreadyExistsException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
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
            $event->setResponse(new JsonResponse([
                'success' => false,
                'error' => [
                    'code' => 'user_already_exists',
                    'message' => $throwable->getMessage(),
                ],
            ], Response::HTTP_CONFLICT));

            return;
        }

        if ($throwable instanceof UnprocessableEntityHttpException) {
            $event->setResponse(new JsonResponse([
                'success' => false,
                'error' => [
                    'code' => 'validation_error',
                    'message' => 'Validation failed.',
                    'details' => $this->extractValidationErrors($throwable),
                ],
            ], Response::HTTP_UNPROCESSABLE_ENTITY));

            return;
        }

        if ($throwable instanceof BadRequestHttpException) {
            $event->setResponse(new JsonResponse([
                'success' => false,
                'error' => [
                    'code' => 'bad_request',
                    'message' => $throwable->getMessage(),
                ],
            ], Response::HTTP_BAD_REQUEST));

            return;
        }

        if ($throwable instanceof AccessDeniedException) {
            $event->setResponse(new JsonResponse([
                'success' => false,
                'error' => [
                    'code' => 'access_denied',
                    'message' => $throwable->getMessage() !== '' ? $throwable->getMessage() : 'Access denied.',
                ],
            ], Response::HTTP_FORBIDDEN));

            return;
        }

        if ($throwable instanceof HttpExceptionInterface) {
            $event->setResponse(new JsonResponse([
                'success' => false,
                'error' => [
                    'code' => 'http_error',
                    'message' => $throwable->getMessage() !== '' ? $throwable->getMessage() : Response::$statusTexts[$throwable->getStatusCode()],
                ],
            ], $throwable->getStatusCode(), $throwable->getHeaders()));
        }
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
