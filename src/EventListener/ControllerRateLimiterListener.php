<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Attribute\RateLimiter;
use App\Factory\ExceptionResponseFactory;
use App\RateLimiter\RateLimiterRegistry;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

final readonly class ControllerRateLimiterListener
{
    public function __construct(
        private RateLimiterRegistry $rateLimiterRegistry,
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


        $limiter = $this->rateLimiterRegistry->getLimiter(policy: $rateLimiterAttribute->policy, request: $event->getRequest());

        $limit = $limiter->consume();

        if (!$limit->isAccepted()) {
            $statusCode = Response::HTTP_TOO_MANY_REQUESTS;
            $response = $this->exceptionFactory->create(
                type: 'too-many-requests',
                statusCode: $statusCode,
                title: Response::$statusTexts[$statusCode] ?? 'Unknown Error',
                detail: 'Too many requests, please try again later.',
            );

            $secondsToRetry = $limit->getRetryAfter()->getTimestamp() - time();

            $response->headers->set('Retry-After', (string) $secondsToRetry);

            $event->setController(static fn() => $response);
        }
    }
}
