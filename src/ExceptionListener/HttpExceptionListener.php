<?php

declare(strict_types=1);

namespace App\ExceptionListener;

use App\DTO\Error\ErrorResponseDTO;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

#[AsEventListener(event: 'kernel.exception', priority: 9)]
final readonly class HttpExceptionListener
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

        if (!$exception instanceof HttpExceptionInterface) {
            return;
        }

        $type = 'http-exception';
        $statusCode = $exception->getStatusCode();
        $title = Response::$statusTexts[$statusCode] ?? 'Unknown Error';
        $detail = $exception->getMessage();
        $trace = $this->isDebug ? $exception->getTraceAsString() : null;

        $dto = new ErrorResponseDTO($type, $statusCode, $title, $detail, null, $trace);
        $data = $this->serializer->serialize(
            $dto,
            JsonEncoder::FORMAT,
            [
                AbstractObjectNormalizer::SKIP_NULL_VALUES => true,
            ]
        );
        $response = new JsonResponse($data, $statusCode, ['Content-Type' => 'application/problem+json'], true);

        $response->headers->add($exception->getHeaders());

        $event->setResponse($response);
    }
}
