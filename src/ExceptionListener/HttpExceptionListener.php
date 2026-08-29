<?php

declare(strict_types=1);

namespace App\ExceptionListener;

use App\Factory\ExceptionResponseFactory;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

#[AsEventListener(event: 'kernel.exception', priority: 9)]
final readonly class HttpExceptionListener
{
    public function __construct(
        private ExceptionResponseFactory $exceptionFactory,
        private bool $isDebug,
    ) {
    }

    /**
     * @throws ExceptionInterface
     */
    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if (!$exception instanceof HttpExceptionInterface) {
            return;
        }

        $type = 'http-exception';
        $statusCode = $exception->getStatusCode();
        $title = Response::$statusTexts[$statusCode] ?? 'Unknown Error';
        $detail = $exception->getMessage();
        $trace = $this->isDebug ? $exception->getTraceAsString() : null;

        $response = $this->exceptionFactory->create(
            type: $type,
            statusCode: $statusCode,
            title: $title,
            detail: $detail,
            trace: $trace
        );

        $response->headers->add($exception->getHeaders());

        $event->setResponse($response);
    }
}
