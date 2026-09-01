<?php

declare(strict_types=1);

namespace App\EventListener\Exception;

use App\Factory\ExceptionResponseFactory;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
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
        #[Autowire(param: 'kernel.debug')]
        private bool $isDebug,
    ) {}

    /**
     * @throws ExceptionInterface
     */
    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if (!$exception instanceof HttpExceptionInterface) {
            return;
        }

        $statusCode = $exception->getStatusCode();

        $response = $this->exceptionFactory->create(
            type: 'http-exception',
            statusCode: $statusCode,
            title: Response::$statusTexts[$statusCode] ?? 'Unknown Error',
            detail: $exception->getMessage(),
            trace: $this->isDebug ? $exception->getTraceAsString() : null,
        );

        $response->headers->add($exception->getHeaders());

        $event->setResponse($response);
    }
}
