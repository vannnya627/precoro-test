<?php

declare(strict_types=1);

namespace App\EventListener\Security;

use App\Exception\ApiExceptionInterface;
use App\Factory\ExceptionResponseFactory;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationFailureEvent;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\TooManyLoginAttemptsAuthenticationException;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

#[AsEventListener(event: 'lexik_jwt_authentication.on_authentication_failure', priority: 5)]
#[AsEventListener(event: 'lexik_jwt_authentication.on_jwt_invalid', priority: 5)]
#[AsEventListener(event: 'lexik_jwt_authentication.on_jwt_not_found', priority: 5)]
#[AsEventListener(event: 'lexik_jwt_authentication.on_jwt_expired', priority: 5)]
final readonly class JwtAuthenticationFailureListener
{
    public function __construct(
        private ExceptionResponseFactory $exceptionFactory,
        #[Autowire(param: 'kernel.debug')]
        private bool $isDebug,
    ) {}

    /**
     * @throws ExceptionInterface
     */
    public function __invoke(AuthenticationFailureEvent $event): void
    {
        $exception = $event->getException();

        [$type, $statusCode, $detail] = match (true) {
            $exception instanceof TooManyLoginAttemptsAuthenticationException => [
                'too-many-requests',
                Response::HTTP_TOO_MANY_REQUESTS,
                'Забагато невдалих спроб входу. Будь ласка, спробуйте пізніше.',
            ],
            $exception instanceof BadCredentialsException => [
                'invalid-credentials',
                Response::HTTP_UNAUTHORIZED,
                'Невірний логін або пароль.',
            ],
            default => [
                'jwt-error',
                Response::HTTP_UNAUTHORIZED,
                $exception->getMessageKey(),
            ],
        };

        $context = [];
        if ($exception instanceof ApiExceptionInterface) {
            $context = $exception->getContext();
        }

        $response = $this->exceptionFactory->create(
            type: $type,
            statusCode: $statusCode,
            title: Response::$statusTexts[$statusCode] ?? 'Unknown Error',
            detail: $detail,
            context: [] === $context ? null : $context,
            trace: $this->isDebug ? $exception->getTraceAsString() : null,
        );

        $event->setResponse($response);
    }
}
