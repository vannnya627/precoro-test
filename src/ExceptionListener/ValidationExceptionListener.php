<?php

declare(strict_types=1);

namespace App\ExceptionListener;

use App\DTO\Error\ErrorResponseDTO;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;

#[AsEventListener(event: 'kernel.exception', priority: 10)]
final readonly class ValidationExceptionListener
{
    public function __construct(
        private SerializerInterface $serializer,
        private bool $isDebug,
    ) {
    }

    /**
     * @throws ExceptionInterface
     */
    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        $validationException = null;

        while (null !== $exception) {
            if ($exception instanceof ValidationFailedException) {
                $validationException = $exception;
                break;
            }
            $exception = $exception->getPrevious();
        }

        if (null === $validationException) {
            return;
        }

        /**
         * @var array<string, list<string>> $errors
         */
        $errors = [];

        foreach ($validationException->getViolations() as $violation) {
            $errors[$violation->getPropertyPath()][] = (string) $violation->getMessage();
        }
        $type = 'validation-error';
        $statusCode = Response::HTTP_UNPROCESSABLE_ENTITY;
        $title = Response::$statusTexts[$statusCode] ?? 'Unknown Error';
        $detail = 'The provided data is invalid. Please check the "errors" property for more details.';
        $trace = $this->isDebug ? $exception->getTraceAsString() : null;

        $dto = new ErrorResponseDTO($type, $statusCode, $title, $detail, $errors, $trace);
        $data = $this->serializer->serialize($dto, JsonEncoder::FORMAT,
            [
                AbstractObjectNormalizer::SKIP_NULL_VALUES => true,
            ]
        );

        $event->setResponse(new JsonResponse($data, $statusCode, ['Content-Type' => 'application/problem+json'], true));
    }
}
