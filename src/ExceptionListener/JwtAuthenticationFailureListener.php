<?php

declare(strict_types=1);

namespace App\ExceptionListener;

use App\Exception\ApiExceptionInterface;
use App\Factory\ExceptionResponseFactory;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationFailureEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

#[AsEventListener(event: 'lexik_jwt_authentication.on_authentication_failure', priority: 5)]
#[AsEventListener(event: 'lexik_jwt_authentication.on_jwt_invalid', priority: 5)]
#[AsEventListener(event: 'lexik_jwt_authentication.on_jwt_not_found', priority: 5)]
#[AsEventListener(event: 'lexik_jwt_authentication.on_jwt_expired', priority: 5)]
final readonly class JwtAuthenticationFailureListener
{
    public function __construct(
        private ExceptionResponseFactory $exceptionFactory,
        private bool $isDebug,
    ) {
    }

    /**
     * @throws ExceptionInterface
     */
    public function __invoke(AuthenticationFailureEvent $event): void
    {
        $exception = $event->getException();

        $type = $exception instanceof BadCredentialsException ? 'invalid-credentials' : 'jwt-error';
        $statusCode = Response::HTTP_UNAUTHORIZED;
        $title = Response::$statusTexts[$statusCode] ?? 'Unknown Error';

        $context = [];
        if ($exception instanceof ApiExceptionInterface) {
            $context = $exception->getContext();
        }

        $detail = $exception->getMessageKey();
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
