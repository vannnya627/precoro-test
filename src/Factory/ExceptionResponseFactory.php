<?php

declare(strict_types=1);

namespace App\Factory;

use App\DTO\Error\ErrorResponseDTO;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

final readonly class ExceptionResponseFactory
{
    public function __construct(
        private SerializerInterface $serializer,
    ) {
    }

    /**
     * @param array<string, mixed>|null $context
     *
     * @throws ExceptionInterface
     */
    public function create(
        string $type,
        int $statusCode,
        string $title,
        string $detail,
        ?array $context = null,
        ?string $trace = null,
    ): JsonResponse {
        $dto = new ErrorResponseDTO(
            type: $type,
            status: $statusCode,
            title: $title,
            detail: $detail,
            context: empty($context) ? null : $context,
            trace: $trace
        );

        $data = $this->serializer->serialize($dto, JsonEncoder::FORMAT, [
            AbstractObjectNormalizer::SKIP_NULL_VALUES => true,
        ]);

        return new JsonResponse($data, $statusCode, ['Content-Type' => 'application/problem+json'], true);
    }
}
