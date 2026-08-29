<?php

declare(strict_types=1);

namespace App\Listener\Exception;

use App\Exception\ApiExceptionInterface;
use App\ExceptionHandler\ExceptionMappingDTO;
use App\ExceptionHandler\ExceptionMappingResolver;
use App\Factory\ExceptionResponseFactory;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

#[AsEventListener(event: 'kernel.exception', priority: -1)]
final readonly class ApiExceptionListener
{
    private const string FALLBACK_TYPE = 'https://datatracker.ietf.org/doc/html/rfc7231#section-6.6.1';

    public function __construct(
        private ExceptionMappingResolver $resolver,
        private LoggerInterface $logger,
        private ExceptionResponseFactory $exceptionFactory,
        private bool $isDebug,
    ) {
    }

    /**
     * @throws ExceptionInterface
     */
    public function __invoke(ExceptionEvent $event): void
    {
        if ($event->hasResponse()) {
            return;
        }

        $exception = $event->getThrowable();

        $mapping = $this->resolver->resolve(get_class($exception));
        if (null === $mapping) {
            $mapping = ExceptionMappingDTO::fromTypeAndCode(self::FALLBACK_TYPE, Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $type = $mapping->type;
        $statusCode = $mapping->code;
        $title = Response::$statusTexts[$statusCode] ?? 'Unknown Error';
        $detail = ($this->isDebug || $statusCode < Response::HTTP_INTERNAL_SERVER_ERROR) ? $exception->getMessage() : 'An unexpected error occurred. Please try again later';

        $context = [];
        if ($exception instanceof ApiExceptionInterface) {
            $context = $exception->getContext();
        }

        if ($mapping->code >= Response::HTTP_INTERNAL_SERVER_ERROR || $mapping->loggable) {
            $this->logger->error($exception->getMessage(), [
                'url' => $event->getRequest()->getUri(),
                'context' => $context,
                'trace' => $this->isDebug ? $exception->getTraceAsString() : null,
            ]);
        }

        $trace = $this->isDebug ? $exception->getTraceAsString() : null;

        $response = $this->exceptionFactory->create(
            type: $type,
            statusCode: $statusCode,
            title: $title,
            detail: $detail,
            context: empty($context) ? null : $context,
            trace: $trace
        );

        $event->setResponse($response);
    }
}
