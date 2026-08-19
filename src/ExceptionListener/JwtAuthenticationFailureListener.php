<?php

declare(strict_types=1);

namespace App\ExceptionListener;

use App\DTO\Error\ErrorResponseDTO;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationFailureEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

#[AsEventListener(event: 'lexik_jwt_authentication.on_authentication_failure', priority: 5)]
#[AsEventListener(event: 'lexik_jwt_authentication.on_jwt_invalid', priority: 5)]
#[AsEventListener(event: 'lexik_jwt_authentication.on_jwt_not_found', priority: 5)]
#[AsEventListener(event: 'lexik_jwt_authentication.on_jwt_expired', priority: 5)]
final readonly class JwtAuthenticationFailureListener
{
    public function __construct(
        private SerializerInterface $serializer,
        private bool $isDebug,
    ) {
    }

    /**
     * @throws ExceptionInterface
     */
    public function __invoke(AuthenticationFailureEvent $event): void
    {
        $type = 'jwt-error';
        $statusCode = Response::HTTP_UNAUTHORIZED;
        $title = Response::$statusTexts[$statusCode] ?? 'Unknown Error';

        $detail = $event->getException()->getMessage();
        $trace = $this->isDebug ? $event->getException()->getTraceAsString() : null;

        $dto = new ErrorResponseDTO($type, $statusCode, $title, $detail, null, $trace);

        $data = $this->serializer->serialize($dto, JsonEncoder::FORMAT, [
            AbstractObjectNormalizer::SKIP_NULL_VALUES => true,
        ]);

        $event->setResponse(new JsonResponse($data, $statusCode, ['Content-Type' => 'application/problem+json'], true));
    }
}
