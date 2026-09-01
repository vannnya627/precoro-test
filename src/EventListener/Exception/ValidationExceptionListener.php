<?php

declare(strict_types=1);

namespace App\EventListener\Exception;

use App\Factory\ExceptionResponseFactory;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Throwable;

#[AsEventListener(event: 'kernel.exception', priority: 10)]
final readonly class ValidationExceptionListener
{
    public function __construct(
        private ExceptionResponseFactory $exceptionFactory,
        private bool $isDebug,
    ) {}

    /**
     * @throws ExceptionInterface
     */
    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        $validationException = null;

        while ($exception instanceof Throwable) {
            if ($exception instanceof ValidationFailedException) {
                $validationException = $exception;
                break;
            }
            $exception = $exception->getPrevious();
        }

        if (!$validationException instanceof ValidationFailedException) {
            return;
        }

        /**
         * @var array<string, list<string>> $context
         */
        $context = [];
        foreach ($validationException->getViolations() as $violation) {
            $context[$violation->getPropertyPath()][] = (string) $violation->getMessage();
        }

        $type = 'validation-error';
        $statusCode = Response::HTTP_UNPROCESSABLE_ENTITY;
        $title = Response::$statusTexts[$statusCode] ?? 'Unknown Error';

        $detail = 'The provided data is invalid. Please check the "errors" property for more details.';
        $trace = $this->isDebug ? $exception->getTraceAsString() : null;

        $response = $this->exceptionFactory->create(
            type: $type,
            statusCode: $statusCode,
            title: $title,
            detail: $detail,
            context: empty($context) ? null : $context,
            trace: $trace,
        );

        $event->setResponse($response);
    }
}
