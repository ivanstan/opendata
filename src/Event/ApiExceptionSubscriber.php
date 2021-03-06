<?php

namespace App\Event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

class ApiExceptionSubscriber implements EventSubscriberInterface
{
    /**
     * Exception handler will fire on exception occurred on a path bellow /api.
     */
    public const API_PATH = '/api';

    private $env;

    public function __construct($env)
    {
        $this->env = $env;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onException',
        ];
    }

    public function onException(GetResponseForExceptionEvent $event): void
    {
        $exception = $event->getException();

        $path = $event->getRequest()->getPathInfo();

        if (strpos($path, self::API_PATH) === false) {
            return;
        }

        if ($exception instanceof NotFoundHttpException) {
            $this->setResponse($event, Response::HTTP_NOT_FOUND, $exception->getMessage());
        }

        if ($exception instanceof AccessDeniedHttpException) {
            $this->setResponse($event, Response::HTTP_FORBIDDEN, 'Forbidden');
        }

        if ('dev' === $this->env) {
            $this->setResponse($event, $exception->getCode(), $exception->getMessage());
        }
    }

    private function setResponse(GetResponseForExceptionEvent $event, int $code, string $message): void
    {
        $response = new JsonResponse(
            [
                'response' => [
                    'code' => $code,
                    'message' => $message,
                ],
            ]
        );

        $event->setResponse($response);
    }
}
