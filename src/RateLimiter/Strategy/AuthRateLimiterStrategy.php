<?php

declare(strict_types=1);

namespace App\RateLimiter\Strategy;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\RateLimiter\LimiterInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;

final readonly class AuthRateLimiterStrategy implements RateLimiterStrategyInterface
{
    public function __construct(
        private RateLimiterFactory $authLimiter,
    ) {}

    public function support(string $policy): bool
    {
        return 'auth' === $policy;
    }

    public function create(Request $request): LimiterInterface
    {
        return  $this->authLimiter->create((string) $request->getClientIp());
    }
}
