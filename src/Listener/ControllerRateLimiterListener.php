<?php

declare(strict_types=1);

namespace App\Listener;

use App\Attribute\RateLimiter;
use App\Factory\ExceptionResponseFactory;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\RateLimiter\LimiterInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

final readonly class ControllerRateLimiterListener
{
    public function __construct(
        private RateLimiterFactory $authLimiter,
        private RateLimiterFactory $nonJwtLimiter,
        private RateLimiterFactory $jwtLimiter,
        private ExceptionResponseFactory $exceptionFactory,
    ) {}

    /**
     * @throws ExceptionInterface
     */
    #[AsEventListener(event: KernelEvents::CONTROLLER)]
    public function onRequestEvent(ControllerEvent $event): void
    {

        $attributes = $event->getAttributes(RateLimiter::class);

        if ([] === $attributes) {
            return;
        }
        /** @var RateLimiter $rateLimiterAttribute */
        $rateLimiterAttribute = $attributes[0];


        $limiter = $this->getLimiter(policy: $rateLimiterAttribute->policy, request: $event->getRequest());

        $limit = $limiter->consume();

        if (!$limit->isAccepted()) {

            $type = 'too-many-requests';
            $statusCode = Response::HTTP_TOO_MANY_REQUESTS;
            $title = Response::$statusTexts[$statusCode] ?? 'Unknown Error';
            $detail = 'Too many requests, please try again later.';

            $response = $this->exceptionFactory->create(
                type: $type,
                statusCode: $statusCode,
                title: $title,
                detail: $detail,
            );

            $secondsToRetry = $limit->getRetryAfter()->getTimestamp() - time();

            $response->headers->set('Retry-After', (string) $secondsToRetry);

            $event->setController(static fn() => $response);
        }
    }

    private function getLimiter(string $policy, Request $request): LimiterInterface
    {
        return match ($policy) {
            'auth' => $this->authLimiter->create((string) $request->getClientIp()),
            'jwt' => $this->jwtLimiter->create(md5(substr((string) $request->headers->get('Authorization'), 7) ?: (string) $request->getClientIp())),
            default => $this->nonJwtLimiter->create((string) $request->getClientIp()),
        };
    }
}
